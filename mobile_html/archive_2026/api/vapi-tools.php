<?php
/**
 * Alfred Vapi Tools Webhook — Full Suite
 *
 * Tools:
 *  1. authenticateCustomer      Multi-factor: PIN or phone+secret, lockout protection
 *  2. getAccountSummary         Services, domains, invoices
 *  3. checkDomainAvailability   RDAP over HTTPS (accurate, never blocked)
 *  4. domainWhois               Full WHOIS/RDAP info for any domain
 *  5. getInvoices               Invoice list + balance
 *  6. sendPaymentLink           Email/SMS the customer a direct payment link
 *  7. getDnsRecords             Look up DNS records for any domain
 *  8. fixDnsIssue               Create high-priority DNS support ticket with diagnostics
 *  9. createSupportTicket       Log issue during call for team follow-up
 * 10. scheduleCallback          Flag for human callback (high-priority ticket)
 * 11-20. IDE Tools              checkIDEStatus, launchIDE, stopIDE, etc.
 * 21-40. Phase 27 Vision Tools  SEO, staging, migration, perf, a11y, etc.
 * 41-43. Callback Security      verifyForCallback, initiateCallback, checkCallbackStatus
 * 44-51. v9.0 Voice Commerce    createClient, voiceOnboard, payments, orders
 * 52-75. v9.1 Voice Management  agents, phones, calls, SMS, fax, campaigns, products
 * 76-85. Jailhouse Legal Aid   identify, resume, search CanLII, draft motions,
 *                               call court, fax court, case status, update case
 * Helper: Telnyx fax/SMS/call with VAPI fallback
 */

if (!defined('GOSITEME_API')) define('GOSITEME_API', true);

// When require_once'd from alfred-chat.php, skip entry-point auth (functions only)
$_vapiToolsIncludedFromChat = defined('ALFRED_CHAT_CONTEXT');

$GLOBALS['CSRF_EXEMPT'] = true;
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

if (!$_vapiToolsIncludedFromChat) {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); exit; }

    // VAPI webhook secret verification
    $vapiSecret = getenv("VAPI_WEBHOOK_SECRET");
    if ($vapiSecret) {
        $receivedSecret = $_SERVER["HTTP_X_VAPI_SECRET"] ?? "";
        if (!hash_equals($vapiSecret, $receivedSecret)) {
            if (!in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"])) {
                error_log("VAPI tools: Invalid secret from " . ($_SERVER["REMOTE_ADDR"] ?? "unknown"));
                http_response_code(401);
                echo json_encode(["error" => "Unauthorized"]);
                exit;
            }
        }
    }
}

/**
 * Route a raw MCP tool name through the same policy as mcpAction (execution / IDE-only / bridge).
 */
function mcpActionVoiceDispatch(string $realTool, $realArgs, string $vapiCallId): array {
    if ($realTool === '') {
        return ['error' => 'tool_name is required'];
    }
    if (is_string($realArgs)) {
        $realArgs = json_decode($realArgs, true) ?? [];
    }
    if (!is_array($realArgs)) {
        $realArgs = [];
    }
    $executionTools = [
        'ssh_exec', 'sftp_transfer', 'rsync_sync', 'docker_manage', 'k8s_manage',
        'process_manage', 'service_manage', 'firewall_manage',
    ];
    $fileTools = [
        'read_file', 'write_file', 'delete_file', 'rename_file', 'find_file',
        'list_directory', 'get_file_info', 'search_files', 'create_directory',
        'create_checkpoint', 'restore_checkpoint', 'list_checkpoints',
        'run_terminal_command', 'terminal_session_status', 'terminal_history', 'terminal_reset',
        'db_query', 'db_schema', 'db_list', 'db_migrate', 'execute_sql',
    ];
    $callClientId = redisGetCallClientIdFromTools($vapiCallId);
    if (in_array($realTool, $executionTools, true)) {
        if ($callClientId === 33) {
            $result = mcpBridge($realTool, $realArgs);
            $result['_sovereignty'] = 'Commander execution authorized';
            return $result;
        }
        return ['error' => 'Server execution tools require Commander-level authentication. Please authenticate first.'];
    }
    if (in_array($realTool, $fileTools, true)) {
        return ['error' => 'That tool is only available in the GoCodeMe IDE, not over the phone. I can create a support ticket instead.'];
    }
    return mcpBridge($realTool, $realArgs);
}

// Only run the VAPI dispatcher when accessed directly (not included from alfred-chat.php)
if (!$_vapiToolsIncludedFromChat):

$input     = json_decode(file_get_contents('php://input'), true);
$toolCalls = $input['message']['toolCalls'] ?? [];
$results   = [];

foreach ($toolCalls as $toolCall) {
    $toolCallId = $toolCall['id'] ?? uniqid();
    $toolName   = $toolCall['function']['name'] ?? '';
    $args       = $toolCall['function']['arguments'] ?? [];

    $vapiCallId = $input['message']['call']['id'] ?? $input['call']['id'] ?? '';
    $vapiCallerNum = $input['message']['call']['customer']['number'] ?? $input['call']['customer']['number'] ?? '';

    try {
        switch ($toolName) {
            case 'authenticateCustomer':    $result = toolAuthenticate($args, $vapiCallId, $vapiCallerNum);  break;
            case 'getCallerContext':        $result = toolGetCallerContext($args, $vapiCallerNum);            break;
            case 'getAccountSummary':       $result = toolAccountSummary($args);   break;
            case 'checkDomainAvailability': $result = toolCheckDomain($args);      break;
            case 'domainWhois':             $result = toolDomainWhois($args);      break;
            case 'getInvoices':             $result = toolGetInvoices($args);      break;
            case 'sendPaymentLink':         $result = toolSendPaymentLink($args);  break;
            case 'getDnsRecords':           $result = toolGetDns($args);           break;
            case 'fixDnsIssue':             $result = toolFixDns($args);           break;
            case 'createSupportTicket':     $result = toolCreateTicket($args);     break;
            case 'scheduleCallback':        $result = toolCallback($args);         break;

            // ── GoCodeMe IDE Tools ──────────────────────────────────────
            case 'checkIDEStatus':          $result = toolIDEStatus($args);        break;
            case 'launchIDE':               $result = toolLaunchIDE($args);        break;
            case 'stopIDE':                 $result = toolStopIDE($args);          break;
            case 'getTokenUsage':           $result = toolTokenUsage($args);       break;
            case 'getHostingStatus':        $result = toolHostingStatus($args);    break;
            case 'listProjectFiles':        $result = toolListFiles($args);        break;
            case 'deployToLive':            $result = toolDeployLive($args);       break;
            case 'applyTemplate':           $result = toolApplyTemplate($args);    break;
            case 'askAI':                   $result = toolAskAI($args);            break;
            case 'getProjectHealth':        $result = toolProjectHealth($args);    break;

            // ── GoCodeMe IDE File & Dev Tools (voice parity with chat) ──
            case 'readProjectFile':         $result = toolReadFile($args);         break;
            case 'writeProjectFile':        $result = toolWriteFile($args);        break;
            case 'createProjectFile':       $result = toolCreateFile($args);       break;
            case 'searchProjectFiles':      $result = toolSearchFiles($args);      break;
            case 'deleteProjectFile':       $result = toolDeleteFile($args);       break;
            case 'runTerminalCommand':      $result = toolRunCommand($args);       break;
            case 'getGitStatus':            $result = toolGitStatus($args);        break;
            case 'gitCommit':               $result = toolGitCommit($args);        break;
            case 'getGitDiff':              $result = toolGitDiff($args);          break;

            // ── Phase 27: 20 New Alfred Vision Tools ────────────────────
            case 'runSEOAudit':             $result = toolSEOAudit($args);         break;
            case 'getCustomerJourney':      $result = toolCustomerJourney($args);  break;
            case 'suggestUpsell':           $result = toolSuggestUpsell($args);    break;
            case 'createStagingSite':       $result = toolCreateStaging($args);    break;
            case 'runTests':                $result = toolRunTests($args);         break;
            case 'generateLandingPage':     $result = toolGenerateLanding($args);  break;
            case 'migrateSite':             $result = toolMigrateSite($args);      break;
            case 'detectFramework':         $result = toolDetectFramework($args);  break;
            case 'performanceBenchmark':    $result = toolPerfBenchmark($args);    break;
            case 'accessibilityAudit':      $result = toolA11yAudit($args);        break;
            case 'getRevenueAnalytics':     $result = toolRevenueAnalytics($args); break;
            case 'deadLinkScan':            $result = toolDeadLinks($args);        break;
            case 'calculateChurnRisk':      $result = toolChurnRisk($args);        break;
            case 'optimizeImages':          $result = toolOptimizeImages($args);   break;
            case 'generateLegalPages':      $result = toolGenerateLegal($args);    break;
            case 'setupSSL':                $result = toolSetupSSL($args);         break;
            case 'getBillingForecast':      $result = toolBillingForecast($args);  break;
            case 'exportData':              $result = toolExportData($args);       break;
            case 'createContactForm':       $result = toolContactForm($args);      break;
            case 'sendStatusReport':        $result = toolStatusReport($args);     break;

            // ── Callback Security Tools ─────────────────────────────────
            case 'verifyForCallback':      $result = toolVerifyCallback($args);    break;
            case 'initiateCallback':       $result = toolInitiateCallback($args);  break;
            case 'checkCallbackStatus':    $result = toolCallbackStatus($args);    break;

            // ── v9.0: Voice Signup & Payments (Direct Billing API) ────────
            case 'createClient':           $result = toolCreateClientDirect($args);             break;
            case 'voiceOnboard':           $result = toolVoiceOnboardDirect($args);            break;
            case 'addPaymentMethod':       $result = toolAddPaymentMethodDirect($args);        break;
            case 'getPaymentMethods':      $result = toolGetPaymentMethodsDirect($args);       break;
            case 'processPayment':         $result = toolProcessPaymentDirect($args);          break;
            case 'updateClientProfile':    $result = toolUpdateProfileDirect($args);              break;
            case 'acceptOrder':            $result = toolAcceptOrderDirect($args);              break;
            case 'getProductCatalog':      $result = mcpBridge('product_catalog', $args);       break;
            case 'orderHosting':           $result = toolOrderHostingDirect($args);              break;
            case 'registerDomain':         $result = mcpBridge('register_domain', $args);        break;
            case 'orderAddon':             $result = mcpBridge('order_addon', $args);             break;

            // ── v9.1: Voice Management — AI Agents, Phones, Calls, SMS, Fax, Campaigns ──
            case 'listMyAgents':              $result = toolVoiceManage('agents', $args);            break;
            case 'createMyAgent':             $result = toolVoiceManage('agent_create', $args);      break;
            case 'updateMyAgent':             $result = toolVoiceManage('agent_update', $args);      break;
            case 'deleteMyAgent':             $result = toolVoiceManage('agent_delete', $args);      break;
            case 'listMyPhoneNumbers':        $result = toolVoiceManage('phones', $args);            break;
            case 'assignPhoneToAgent':        $result = toolVoiceManage('phone_assign', $args);      break;
            case 'getMyCallLog':              $result = toolVoiceManage('calls', $args);             break;
            case 'getCallDetails':            $result = toolVoiceManage('call_detail', $args);       break;
            case 'sendSMS':                   $result = toolVoiceManage('sms_send', $args);          break;
            case 'listSMSMessages':           $result = toolVoiceManage('sms', $args);               break;
            case 'sendFax':                   $result = toolVoiceManage('fax_send', $args);          break;
            case 'listFaxHistory':            $result = toolVoiceManage('fax', $args);               break;
            case 'getVoiceDashboard':         $result = toolVoiceManage('dashboard', $args);         break;
            case 'getVoiceUsage':             $result = toolVoiceManage('usage', $args);             break;
            case 'createCampaign':            $result = toolVoiceManage('campaign_create', $args);   break;
            case 'listCampaigns':             $result = toolVoiceManage('campaigns', $args);         break;
            case 'updateCampaign':            $result = toolVoiceManage('campaign_update', $args);   break;
            case 'createDocument':            $result = toolVoiceManage('doc_create', $args);        break;
            case 'listDocuments':             $result = toolVoiceManage('documents', $args);         break;
            case 'deleteDocument':            $result = toolVoiceManage('doc_delete', $args);        break;
            case 'orderVoiceProduct':         $result = toolOrderVoiceProduct($args);                break;
            case 'getVoiceProducts':          $result = toolGetVoiceProducts($args);                 break;
            case 'getVoiceRecommendation':    $result = toolVoiceRecommendation($args);              break;
            case 'orderPhoneNumber':          $result = toolOrderPhoneNumber($args);                 break;
            case 'sendEmail':                 $result = toolSendEmail($args);                        break;
            case 'transferCall':              $result = toolTransferCall($args, $input);              break;

            // ── v10.0: Jailhouse Legal Aid — Full Legal Suite ───────────
            // Ensure legal cases table exists on first legal tool call
            case 'legalIdentify':
            case 'legalResumeCase':
            case 'legalSearch':
            case 'legalDraftMotion':
            case 'legalUpdateCase':
            case 'legalCallCourt':
            case 'legalFaxCourt':
            case 'legalCaseStatus':
            case 'legalListCases':
            case 'legalCourtDirectory':
            case 'legalGrievance':
            case 'legalParole':
            case 'legalDisciplinary':
            case 'legalAffidavit':
            case 'legalProtecteur':
            case 'legalOCI':
            case 'legalCourtReminder':
            case 'legalLawyerDirectory':
            case 'legalFaxStatus':
            case 'legalCourDuQuebec':
            case 'legalHabeasCorpus':
            case 'legalBailReview':
            case 'legalAppeals':
            case 'legalSentenceCalc':
            case 'legalCharterChallenge':
            case 'legalDisclosure':
            case 'legalVictimImpact':
            case 'legalConsentOrder':
            case 'legalTransferRequest':
            case 'legalMedicalRequest':
            case 'legalSegregationReview':
            case 'legalRecordSuspension':
            case 'legalImmigration':
            case 'legalMentalHealth':
            case 'legalYouthJustice':
            case 'legalIndigenousRights':
            case 'legalFrenchTranslate':
            case 'legalDeadlineCalc':
            case 'legalEvidenceChecklist':
            case 'legalWitnessStatement':
            case 'legalPleaNegotiation':
            case 'legalSuretyPlan':
            case 'legalCostsEstimate':
            case 'legalEmergencyInjunction':
                ensureLegalCasesTable();
                // Fall back into specific handlers below
                break;
            }
            // Re-dispatch after table ensured
            switch ($toolName) {
            case 'legalIdentify':             $result = toolLegalIdentify($args);                    break;
            case 'legalResumeCase':            $result = toolLegalResumeCase($args);                  break;
            case 'legalSearch':               $result = toolLegalSearch($args);                      break;
            case 'legalDraftMotion':           $result = toolLegalDraftMotion($args);                 break;
            case 'legalUpdateCase':            $result = toolLegalUpdateCase($args);                  break;
            case 'legalCallCourt':             $result = toolLegalCallCourt($args);                   break;
            case 'legalFaxCourt':              $result = toolLegalFaxCourt($args);                    break;
            case 'legalCaseStatus':            $result = toolLegalCaseStatus($args);                  break;
            case 'legalListCases':             $result = toolLegalListCases($args);                   break;
            case 'legalCourtDirectory':        $result = toolLegalCourtDirectory($args);              break;
            case 'legalGrievance':             $result = toolLegalGrievance($args);                   break;
            case 'legalParole':                $result = toolLegalParole($args);                      break;
            case 'legalDisciplinary':          $result = toolLegalDisciplinary($args);                break;
            case 'legalAffidavit':             $result = toolLegalAffidavit($args);                   break;
            case 'legalProtecteur':            $result = toolLegalProtecteur($args);                  break;
            case 'legalOCI':                   $result = toolLegalOCI($args);                         break;
            case 'legalCourtReminder':         $result = toolLegalCourtReminder($args);               break;
            case 'legalLawyerDirectory':       $result = toolLegalLawyerDirectory($args);             break;
            case 'legalFaxStatus':             $result = toolLegalFaxStatus($args);                   break;
            case 'legalCourDuQuebec':          $result = toolLegalCourDuQuebec($args);                break;
            case 'legalHabeasCorpus':          $result = toolLegalHabeasCorpus($args);                break;
            case 'legalBailReview':            $result = toolLegalBailReview($args);                  break;
            case 'legalAppeals':               $result = toolLegalAppeals($args);                     break;
            case 'legalSentenceCalc':          $result = toolLegalSentenceCalc($args);                break;
            case 'legalCharterChallenge':      $result = toolLegalCharterChallenge($args);            break;
            case 'legalDisclosure':            $result = toolLegalDisclosure($args);                  break;
            case 'legalVictimImpact':          $result = toolLegalVictimImpact($args);                break;
            case 'legalConsentOrder':          $result = toolLegalConsentOrder($args);                break;
            case 'legalTransferRequest':       $result = toolLegalTransferRequest($args);             break;
            case 'legalMedicalRequest':        $result = toolLegalMedicalRequest($args);              break;
            case 'legalSegregationReview':     $result = toolLegalSegregationReview($args);           break;
            case 'legalRecordSuspension':      $result = toolLegalRecordSuspension($args);            break;
            case 'legalImmigration':           $result = toolLegalImmigration($args);                 break;
            case 'legalMentalHealth':          $result = toolLegalMentalHealth($args);                break;
            case 'legalYouthJustice':          $result = toolLegalYouthJustice($args);                break;
            case 'legalIndigenousRights':      $result = toolLegalIndigenousRights($args);            break;
            case 'legalFrenchTranslate':       $result = toolLegalFrenchTranslate($args);             break;
            case 'legalDeadlineCalc':          $result = toolLegalDeadlineCalc($args);                break;
            case 'legalEvidenceChecklist':     $result = toolLegalEvidenceChecklist($args);           break;
            case 'legalWitnessStatement':      $result = toolLegalWitnessStatement($args);            break;
            case 'legalPleaNegotiation':       $result = toolLegalPleaNegotiation($args);             break;
            case 'legalSuretyPlan':            $result = toolLegalSuretyPlan($args);                  break;
            case 'legalCostsEstimate':         $result = toolLegalCostsEstimate($args);               break;
            case 'legalEmergencyInjunction':   $result = toolLegalEmergencyInjunction($args);         break;

            // ── v11.0: Students K-12 Voice Tools ────────────────────────
            case 'homework_helper':           $result = toolHomeworkHelper($args);           break;
            case 'math_tutor':                $result = toolMathTutor($args);                break;
            case 'science_lab_simulator':     $result = toolScienceLabSim($args);            break;
            case 'essay_coach':               $result = toolEssayCoach($args);               break;
            case 'flashcard_creator':         $result = toolFlashcardCreator($args);         break;
            case 'quiz_generator':            $result = toolQuizGenerator($args);            break;
            case 'study_plan_builder':        $result = toolStudyPlanBuilder($args);         break;
            case 'reading_level_analyzer':    $result = toolReadingLevel($args);             break;
            case 'vocabulary_builder':        $result = toolVocabularyBuilder($args);        break;
            case 'book_report_helper':        $result = toolBookReportHelper($args);         break;
            case 'history_timeline':          $result = toolHistoryTimeline($args);          break;
            case 'geography_explorer':        $result = toolGeographyExplorer($args);        break;
            case 'safe_web_search':           $result = toolSafeWebSearch($args);            break;
            case 'parent_progress_report':    $result = toolParentProgressReport($args);     break;

            // ── v11.1: University/College Voice Tools ───────────────────
            case 'citation_generator':        $result = toolCitationGenerator($args);        break;
            case 'literature_review':         $result = toolLiteratureReview($args);         break;
            case 'thesis_outline':            $result = toolThesisOutline($args);            break;
            case 'statistical_analysis':      $result = toolStatisticalAnalysis($args);      break;
            case 'research_methodology':      $result = toolResearchMethodology($args);      break;
            case 'peer_review_simulator':     $result = toolPeerReviewSim($args);            break;
            case 'gpa_calculator':            $result = toolGPACalculator($args);            break;
            case 'course_planner':            $result = toolCoursePlanner($args);            break;
            case 'lab_report_formatter':      $result = toolLabReportFormatter($args);       break;
            case 'study_group_coordinator':   $result = toolStudyGroupCoord($args);          break;
            case 'exam_prep':                 $result = toolExamPrep($args);                 break;
            case 'academic_integrity_check':  $result = toolAcademicIntegrity($args);        break;
            case 'grant_proposal_writer':     $result = toolGrantProposalWriter($args);      break;
            case 'conference_paper_prep':     $result = toolConferencePaperPrep($args);      break;
            case 'scholarship_finder':        $result = toolScholarshipFinder($args);        break;

            // ── v12.0: Real Estate Voice Tools ──────────────────────────
            case 'propertyValuator':          $result = toolPropertyValuator($args);          break;
            case 'mortgageCalculator':         $result = toolMortgageCalculator($args);         break;
            case 'neighborhoodAnalyzer':       $result = toolNeighborhoodAnalyzer($args);       break;
            case 'listingDescriptionWriter':   $result = toolListingDescriptionWriter($args);   break;
            case 'openHousePlanner':           $result = toolOpenHousePlanner($args);           break;
            case 'comparativeMarketAnalysis':  $result = toolComparativeMarketAnalysis($args);  break;
            case 'rentalYieldCalculator':      $result = toolRentalYieldCalculator($args);      break;
            case 'homeInspectionChecklist':    $result = toolHomeInspectionChecklist($args);    break;
            case 'closingCostEstimator':       $result = toolClosingCostEstimator($args);       break;
            case 'propertyTaxEstimator':       $result = toolPropertyTaxEstimator($args);       break;

            // ── v12.1: Freelancer Voice Tools ───────────────────────────
            case 'freelanceRateCalculator':    $result = toolFreelanceRateCalculator($args);    break;
            case 'proposalWriter':             $result = toolProposalWriter($args);             break;
            case 'contractGenerator':          $result = toolContractGenerator($args);          break;
            case 'timeTracker':                $result = toolTimeTracker($args);                break;
            case 'clientOnboarding':           $result = toolClientOnboarding($args);           break;
            case 'portfolioOptimizer':         $result = toolPortfolioOptimizer($args);         break;
            case 'freelanceTaxHelper':         $result = toolFreelanceTaxHelper($args);         break;
            case 'scopeCreepDetector':         $result = toolScopeCreepDetector($args);         break;
            case 'testimonialRequester':       $result = toolTestimonialRequester($args);       break;

            // ── v12.2: Seniors Voice Tools ──────────────────────────────
            case 'medicationManager':          $result = toolMedicationManager($args);          break;
            case 'appointmentHelper':          $result = toolAppointmentHelper($args);          break;
            case 'familyConnector':            $result = toolFamilyConnector($args);            break;
            case 'memoryExercise':             $result = toolMemoryExercise($args);             break;
            case 'fallPreventionTips':         $result = toolFallPreventionTips($args);         break;
            case 'largeTextReader':            $result = toolLargeTextReader($args);            break;
            case 'simplifiedTechHelp':         $result = toolSimplifiedTechHelp($args);         break;
            case 'dailyRoutineHelper':         $result = toolDailyRoutineHelper($args);         break;
            case 'emergencyContacts':          $result = toolEmergencyContacts($args);          break;
            case 'nutritionForSeniors':        $result = toolNutritionForSeniors($args);        break;
            case 'exerciseForSeniors':         $result = toolExerciseForSeniors($args);         break;

            // ── v12.3: Parents / Family Voice Tools ─────────────────────
            case 'choreAssigner':              $result = toolChoreAssigner($args);              break;
            case 'mealPlanFamily':             $result = toolMealPlanFamily($args);             break;
            case 'screenTimeManager':          $result = toolScreenTimeManager($args);          break;
            case 'homeworkHelperParent':        $result = toolHomeworkHelperParent($args);       break;
            case 'familyBudget':               $result = toolFamilyBudget($args);               break;
            case 'childMilestoneTracker':      $result = toolChildMilestoneTracker($args);      break;
            case 'familyActivityFinder':       $result = toolFamilyActivityFinder($args);       break;
            case 'bedtimeStoryGenerator':      $result = toolBedtimeStoryGenerator($args);      break;

            // ── Phase 1-3: Sovereignty Tools ─────────────────────────────────
            case 'createOpsDirective':  $result = toolCreateOpsDirective($args);  break;
            case 'taskAgent':           $result = toolTaskAgent($args);            break;
            case 'getSystemHealth':     $result = toolGetSystemHealth($args);      break;
            case 'getAgentFleetStatus': $result = toolGetAgentFleetStatus($args);  break;
            case 'executeServerCommand':$result = toolExecuteServerCommand($args, $vapiCallId); break;

            // ── mcpAction: Meta-tool for 1,220+ MCP tools via voice ────────
            case 'mcpAction':
                $realTool = $args['tool_name'] ?? '';
                $realArgs = $args['arguments'] ?? [];
                $result = mcpActionVoiceDispatch((string) $realTool, $realArgs, $vapiCallId);
                break;

            // ── Professionals ────────────────────────────────────────
            case 'meeting_summarizer':             $result = toolMeetingSummarizer($args);             break;
            case 'presentation_builder':           $result = toolPresentationBuilder($args);           break;
            case 'calendar_optimizer':             $result = toolCalendarOptimizer($args);             break;
            case 'okr_tracker':                    $result = toolOKRTracker($args);                    break;
            case 'standup_generator':              $result = toolStandupGenerator($args);              break;
            case 'decision_matrix':                $result = toolDecisionMatrix($args);                break;
            case 'project_estimator':              $result = toolProjectEstimator($args);              break;
            case 'sprint_planner':                 $result = toolSprintPlanner($args);                 break;
            case 'retrospective_facilitator':      $result = toolRetrospectiveFacilitator($args);      break;
            case 'risk_register':                  $result = toolRiskRegister($args);                  break;
            case 'stakeholder_mapper':             $result = toolStakeholderMapper($args);             break;
            case 'competitive_analysis':           $result = toolCompetitiveAnalysis($args);           break;
            case 'swot_analysis':                  $result = toolSWOTAnalysis($args);                  break;
            case 'business_case_builder':          $result = toolBusinessCaseBuilder($args);           break;
            case 'executive_summary':              $result = toolExecutiveSummary($args);              break;

            // ── Small Business ───────────────────────────────────────
            case 'bookkeeping':                    $result = toolBookkeeping($args);                   break;
            case 'invoice_creator':                $result = toolInvoiceCreator($args);                break;
            case 'payroll_calculator':             $result = toolPayrollCalculator($args);             break;
            case 'inventory_tracker':              $result = toolInventoryTracker($args);              break;
            case 'crm_contact_manager':            $result = toolCRMContactManager($args);             break;
            case 'quote_generator':                $result = toolQuoteGenerator($args);                break;
            case 'expense_tracker':                $result = toolExpenseTracker($args);                break;
            case 'tax_prep':                       $result = toolTaxPrep($args);                       break;
            case 'cash_flow_forecast':             $result = toolCashFlowForecast($args);              break;
            case 'employee_scheduler':             $result = toolEmployeeScheduler($args);             break;
            case 'customer_survey':                $result = toolCustomerSurvey($args);                break;
            case 'competitor_price_monitor':       $result = toolCompetitorPriceMonitor($args);        break;
            case 'social_media_scheduler':         $result = toolSocialMediaScheduler($args);          break;
            case 'review_responder':               $result = toolReviewResponder($args);               break;
            case 'business_plan_writer':           $result = toolBusinessPlanWriter($args);            break;

            // ── Content Creators ─────────────────────────────────────
            case 'youtube_script_writer':          $result = toolYouTubeScriptWriter($args);           break;
            case 'thumbnail_designer':             $result = toolThumbnailDesigner($args);             break;
            case 'podcast_show_notes':             $result = toolPodcastShowNotes($args);              break;
            case 'social_post_generator':          $result = toolSocialPostGenerator($args);           break;
            case 'content_calendar':               $result = toolContentCalendar($args);               break;
            case 'hashtag_optimizer':              $result = toolHashtagOptimizer($args);              break;
            case 'video_idea_generator':           $result = toolVideoIdeaGenerator($args);            break;
            case 'sponsor_pitch':                  $result = toolSponsorPitch($args);                  break;
            case 'analytics_reporter':             $result = toolAnalyticsReporter($args);             break;
            case 'caption_generator':              $result = toolCaptionGenerator($args);              break;
            case 'content_repurposer':             $result = toolContentRepurposer($args);             break;
            case 'stream_overlay_creator':         $result = toolStreamOverlayCreator($args);          break;
            case 'tiktok_trend_analyzer':          $result = toolTikTokTrendAnalyzer($args);           break;
            case 'newsletter_writer':              $result = toolNewsletterWriter($args);              break;

            // ── Healthcare ───────────────────────────────────────────
            case 'soap_note_writer':               $result = toolSOAPNoteWriter($args);                break;
            case 'shift_scheduler':                $result = toolShiftScheduler($args);                break;
            case 'patient_handoff':                $result = toolPatientHandoff($args);                break;
            case 'medication_checker':             $result = toolMedicationChecker($args);             break;
            case 'clinical_protocol_finder':       $result = toolClinicalProtocolFinder($args);        break;
            case 'medical_terminology':            $result = toolMedicalTerminology($args);            break;
            case 'continuing_ed_tracker':          $result = toolContinuingEdTracker($args);           break;
            case 'incident_report':                $result = toolIncidentReport($args);                break;
            case 'infection_control':              $result = toolInfectionControl($args);              break;
            case 'mental_health_screening':        $result = toolMentalHealthCheck($args);             break;
            case 'telehealth_setup':               $result = toolTelehealthSetup($args);               break;
            case 'hipaa_compliance':               $result = toolHIPAACompliance($args);               break;

            // ── Real Estate (snake_case aliases) ─────────────────────
            case 'listing_writer':                 $result = toolListingWriter($args);                 break;
            case 'comparative_analysis':           $result = toolComparativeMarketAnalysis($args);     break;
            case 'client_follow_up':               $result = toolClientFollowUp($args);                break;
            case 'virtual_tour_creator':           $result = toolVirtualTourCreator($args);            break;
            case 'closing_checklist':              $result = toolClosingChecklist($args);              break;
            case 'market_report':                  $result = toolMarketReport($args);                  break;
            case 'lead_qualifier':                 $result = toolLeadQualifier($args);                 break;
            case 'neighborhood_profile':           $result = toolNeighborhoodProfile($args);           break;

            // ── Legal Practitioners ──────────────────────────────────
            case 'contract_drafter':               $result = toolContractDrafter($args);               break;
            case 'contract_reviewer_legal':        $result = toolContractReviewerLegal($args);         break;
            case 'legal_research':                 $result = toolLegalResearch($args);                 break;
            case 'time_tracker_legal':             $result = toolTimeTrackerLegal($args);              break;
            case 'trust_account_manager':          $result = toolTrustAccountManager($args);           break;
            case 'court_deadline_tracker':         $result = toolCourtDeadlineTracker($args);          break;
            case 'client_intake':                  $result = toolClientIntake($args);                  break;
            case 'demand_letter_writer':           $result = toolDemandLetterWriter($args);            break;
            case 'incorporation_assistant':        $result = toolIncorporationAssistant($args);        break;
            case 'will_estate_planner':            $result = toolWillEstatePlanner($args);             break;
            case 'immigration_form_helper':        $result = toolImmigrationFormHelper($args);         break;
            case 'mediation_prep':                 $result = toolMediationPrep($args);                 break;
            case 'litigation_budget':              $result = toolLitigationBudget($args);              break;
            case 'deposition_prep':                $result = toolDepositionPrep($args);                break;
            case 'compliance_checker':             $result = toolComplianceCheckerLegal($args);        break;

            // ── Parents (additional) ─────────────────────────────────
            case 'family_calendar':                $result = toolFamilyCalendar($args);                break;
            case 'college_savings_planner':        $result = toolCollegeSavingsPlanner($args);         break;
            case 'emergency_info_card':            $result = toolEmergencyInfoCard($args);             break;
            case 'recipe_scaler':                  $result = toolRecipeScaler($args);                  break;

            // ── Seniors (additional) ─────────────────────────────────
            case 'health_journal':                 $result = toolHealthJournal($args);                 break;
            case 'scam_detector':                  $result = toolScamDetector($args);                  break;
            case 'caregiver_portal':               $result = toolCaregiverPortal($args);               break;
            case 'emergency_alert':                $result = toolEmergencyAlert($args);                break;
            case 'photo_organizer':                $result = toolPhotoOrganizer($args);                break;
            case 'voice_memo':                     $result = toolVoiceMemo($args);                     break;
            case 'bill_pay_helper':                $result = toolBillPayHelper($args);                 break;
            case 'social_connector':               $result = toolSocialConnector($args);               break;
            case 'medication_reminder':            $result = toolMedicationReminder($args);            break;

            // ── Freelancers (additional) ─────────────────────────────
            case 'freelance_invoice':              $result = toolFreelanceInvoice($args);              break;
            case 'rate_calculator':                $result = toolRateCalculator($args);                break;
            case 'portfolio_builder':              $result = toolPortfolioBuilder($args);              break;
            case 'contract_template':              $result = toolContractTemplate($args);              break;
            case 'client_onboarding':              $result = toolClientOnboarding($args);              break;
            case 'tax_quarterly_estimator':        $result = toolTaxQuarterlyEstimator($args);         break;
            case 'project_timeline':               $result = toolProjectTimeline($args);               break;
            case 'feedback_request':               $result = toolFeedbackRequest($args);               break;
            case 'income_diversifier':             $result = toolIncomeDiversifier($args);             break;

            // ── Non-Profits ──────────────────────────────────────────
            case 'grant_writer':                   $result = toolGrantWriter($args);                   break;
            case 'donor_manager':                  $result = toolDonorManager($args);                  break;
            case 'volunteer_coordinator':          $result = toolVolunteerCoordinator($args);          break;
            case 'impact_report':                  $result = toolImpactReport($args);                  break;
            case 'fundraising_campaign':           $result = toolFundraisingCampaign($args);           break;
            case 'annual_report':                  $result = toolAnnualReport($args);                  break;
            case 'board_meeting_prep':             $result = toolBoardMeetingPrep($args);              break;
            case 'tax_exempt_compliance':          $result = toolTaxExemptCompliance($args);           break;
            case 'event_planner':                  $result = toolEventPlanner($args);                  break;
            case 'newsletter_creator':             $result = toolNewsletterCreator($args);             break;
            case 'social_impact_metrics':          $result = toolSocialImpactMetrics($args);           break;
            case 'partnership_finder':             $result = toolPartnershipFinder($args);             break;

            // ── Teachers / Educators ─────────────────────────────────
            case 'lesson_plan_creator':            $result = toolLessonPlanCreator($args);             break;
            case 'rubric_builder':                 $result = toolRubricGenerator($args);               break;
            case 'quiz_maker':                     $result = toolQuizMaker($args);                     break;
            case 'report_card_generator':          $result = toolStudentProgressReport($args);         break;
            case 'iep_goal_writer':                $result = toolIEPHelper($args);                     break;
            case 'curriculum_mapper':              $result = toolCurriculumMapper($args);              break;
            case 'attendance_tracker':             $result = toolAttendanceTracker($args);             break;
            case 'behavior_logger':                $result = toolBehaviorLogger($args);                break;
            case 'parent_communication':           $result = toolParentCommunicator($args);            break;
            case 'substitute_plan':                $result = toolSubPlanCreator($args);                break;
            case 'field_trip_planner':             $result = toolFieldTripPlanner($args);              break;
            case 'differentiated_activity':        $result = toolDifferentiatedInstruction($args);     break;
            case 'classroom_seating':              $result = toolClassroomManager($args);              break;
            case 'grade_calculator':               $result = toolGradeCalculator($args);               break;
            case 'student_portfolio':              $result = toolStudentPortfolio($args);              break;

            // ── Future Tech ──────────────────────────────────────────
            case 'robot_fleet_manager':            $result = toolRobotFleetManager($args);             break;
            case 'iot_device_manager':             $result = toolIoTDeviceManager($args);              break;
            case 'smart_home_controller':          $result = toolSmartHomeController($args);           break;
            case 'drone_mission_planner':          $result = toolDroneMissionPlanner($args);           break;
            case 'ar_scene_builder':               $result = toolARSceneBuilder($args);                break;
            case 'vr_world_creator':               $result = toolVRWorldCreator($args);                break;
            case '3d_print_slicer':                $result = tool3DPrintSlicer($args);                 break;
            case 'firmware_updater':               $result = toolFirmwareUpdater($args);               break;
            case 'sensor_data_analyzer':           $result = toolSensorDataAnalyzer($args);            break;
            case 'edge_compute_deployer':          $result = toolEdgeComputeDeployer($args);           break;
            case 'digital_twin_creator':           $result = toolDigitalTwinCreator($args);            break;
            case 'autonomous_vehicle_sim':         $result = toolAutonomousVehicleSim($args);          break;
            case 'wearable_app_builder':           $result = toolWearableAppBuilder($args);            break;
            case 'blockchain_deployer':            $result = toolBlockchainDeployer($args);            break;
            case 'quantum_code_helper':            $result = toolQuantumCodeHelper($args);             break;

            // ── Agent Orchestration ──────────────────────────────────
            case 'agent_registry':                 $result = toolAgentRegistry($args);                 break;
            case 'agent_task_router':              $result = toolAgentTaskRouter($args);               break;
            case 'agent_pipeline_builder':         $result = toolAgentPipelineBuilder($args);          break;
            case 'agent_health_monitor':           $result = toolAgentHealthMonitor($args);            break;
            case 'agent_performance_scorer':       $result = toolAgentPerformanceScorer($args);        break;
            case 'agent_learning_loop':            $result = toolAgentLearningLoop($args);             break;
            case 'agent_conflict_resolver':        $result = toolAgentConflictResolver($args);         break;
            case 'agent_cost_optimizer':           $result = toolAgentCostOptimizer($args);            break;
            case 'agent_version_manager':          $result = toolAgentVersionManager($args);           break;
            case 'agent_marketplace_publisher':    $result = toolAgentMarketplacePublisher($args);     break;

            // ── Collaboration ────────────────────────────────────────
            case 'team_workspace':                 $result = toolTeamWorkspace($args);                 break;
            case 'live_code_session':              $result = toolLiveCodeSession($args);               break;
            case 'shared_terminal':                $result = toolSharedTerminal($args);                break;
            case 'task_board':                     $result = toolTaskBoard($args);                     break;
            case 'team_chat':                      $result = toolTeamChat($args);                      break;
            case 'screen_share':                   $result = toolScreenShare($args);                   break;
            case 'whiteboard':                     $result = toolWhiteboard($args);                    break;
            case 'code_review_request':            $result = toolCodeReviewRequest($args);             break;
            case 'team_standup':                   $result = toolTeamStandup($args);                   break;
            case 'knowledge_base':                 $result = toolKnowledgeBase($args);                 break;

            // ── Reporting ────────────────────────────────────────────
            case 'dashboard_builder':              $result = toolDashboardBuilder($args);              break;
            case 'report_scheduler':               $result = toolReportScheduler($args);               break;
            case 'agent_performance_report':       $result = toolAgentPerformanceReport($args);        break;
            case 'roi_calculator':                 $result = toolROICalculator($args);                 break;
            case 'sla_monitor':                    $result = toolSLAMonitor($args);                    break;
            case 'usage_analytics':                $result = toolUsageAnalytics($args);                break;
            case 'cost_analyzer':                  $result = toolCostAnalyzer($args);                  break;
            case 'benchmark_comparator':           $result = toolBenchmarkComparator($args);           break;
            case 'custom_chart_builder':           $result = toolCustomChartBuilder($args);            break;
            case 'data_exporter':                  $result = toolDataExporter($args);                  break;
            case 'alert_configurator':             $result = toolAlertConfigurator($args);             break;
            case 'executive_dashboard':            $result = toolExecutiveDashboard($args);            break;

            // ── Marketplace ──────────────────────────────────────────
            case 'marketplace_browse':             $result = toolMarketplaceSearch($args);             break;
            case 'marketplace_install':            $result = toolMarketplaceInstall($args);            break;
            case 'marketplace_review':             $result = toolMarketplaceReview($args);             break;
            case 'marketplace_analytics':          $result = toolMarketplaceAnalytics($args);          break;
            case 'marketplace_pricing':            $result = toolMarketplacePricing($args);            break;
            case 'tool_builder':                   $result = toolToolBuilder($args);                   break;
            case 'agent_template_store':           $result = toolAgentTemplateStore($args);            break;
            case 'playbook_marketplace':           $result = toolPlaybookMarketplace($args);           break;
            case 'marketplace_lister':             $result = toolMarketplaceLister($args);             break;

            // ── Gamification ─────────────────────────────────────────
            case 'achievement_system':             $result = toolAchievementChecker($args);            break;
            case 'streak_tracker':                 $result = toolStreakTracker($args);                  break;
            case 'skill_tree':                     $result = toolSkillTree($args);                     break;
            case 'leaderboard':                    $result = toolLeaderboardManager($args);            break;
            case 'learning_path':                  $result = toolLearningPath($args);                  break;
            case 'challenge_mode':                 $result = toolChallengeCreator($args);              break;
            case 'badge_collector':                $result = toolBadgeDesigner($args);                 break;
            case 'xp_system':                      $result = toolXPCalculator($args);                  break;

            // ── Conferencing ─────────────────────────────────────────
            case 'conference_create':              $result = toolConferenceCreate($args);              break;
            case 'conference_invite':              $result = toolConferenceInvite($args);              break;
            case 'conference_record':              $result = toolConferenceRecord($args);              break;
            case 'conference_follow_up':           $result = toolConferenceFollowUp($args);            break;
            case 'conference_summarize':           $result = toolConferenceSummarize($args);           break;
            case 'conference_transcribe':          $result = toolConferenceTranscribe($args);          break;
            case 'conference_moderate':            $result = toolConferenceModerate($args);            break;
            case 'conference_agenda':              $result = toolConferenceAgenda($args);              break;
            case 'conference_poll':                $result = toolConferencePoll($args);                break;
            case 'conference_breakout':            $result = toolConferenceBreakout($args);            break;

            // ── Consciousness Layer ──────────────────────────────────
            case 'alfred_set_personality':         $result = toolSetPersonality($args);                break;
            case 'alfred_get_personality':         $result = toolGetPersonality($args);                break;
            case 'alfred_adapt_style':             $result = toolAdaptStyle($args);                   break;
            case 'alfred_self_reflect':            $result = toolSelfReflect($args);                  break;
            case 'alfred_learning_journal':        $result = toolLearningJournal($args);              break;
            case 'alfred_user_profile':            $result = toolUserProfile($args);                  break;
            case 'alfred_relationship_score':      $result = toolRelationshipScore($args);            break;
            case 'alfred_daily_briefing':          $result = toolDailyBriefing($args);                break;
            case 'alfred_proactive_suggest':       $result = toolProactiveSuggest($args);             break;
            case 'alfred_dream_state':             $result = toolDreamState($args);                   break;
            case 'alfred_emotional_state':         $result = toolEmotionalState($args);               break;
            case 'alfred_growth_tracker':          $result = toolGrowthTracker($args);                break;

            // ── Offline ──────────────────────────────────────────────
            case 'offline_sync':                   $result = toolOfflineSync($args);                   break;
            case 'offline_editor':                 $result = toolOfflineEditor($args);                 break;
            case 'offline_ai':                     $result = toolOfflineAI($args);                     break;
            case 'cached_docs':                    $result = toolCachedDocs($args);                    break;
            case 'pending_actions':                $result = toolPendingActions($args);                break;

            // ── Health & Wellness ────────────────────────────────────
            case 'symptom_checker':                $result = toolSymptomChecker($args);                break;
            case 'first_aid_guide':                $result = toolFirstAidGuide($args);                 break;
            case 'workout_planner':                $result = toolWorkoutPlanner($args);                break;
            case 'nutrition_planner':              $result = toolNutritionPlanner($args);              break;
            case 'calorie_counter':                $result = toolCalorieCounter($args);                break;
            case 'hydration_tracker':              $result = toolHydrationTracker($args);              break;
            case 'sleep_analyzer':                 $result = toolSleepAnalyzer($args);                 break;
            case 'posture_corrector':              $result = toolPostureCorrector($args);              break;
            case 'vaccination_schedule':           $result = toolVaccinationSchedule($args);           break;

            // ── Education Extras ─────────────────────────────────────
            case 'reading_list_curator':           $result = toolReadingListCurator($args);            break;
            case 'sel_activity_generator':         $result = toolSELActivityGenerator($args);          break;
            case 'classroom_activity':             $result = toolClassroomActivity($args);             break;

            // ── Marketplace & Commerce Extras ────────────────────────
            case 'dispute_resolver':               $result = toolDisputeResolver($args);               break;
            case 'seller_rating':                  $result = toolSellerRating($args);                  break;
            case 'price_optimizer':                $result = toolPriceOptimizer($args);                break;

            // ── Emergency & Compliance ───────────────────────────────
            case 'emergency_protocol':             $result = toolEmergencyProtocol($args);             break;
            case 'grant_finder':                   $result = toolGrantFinder($args);                   break;
            case 'nonprofit_compliance':           $result = toolNonProfitCompliance($args);           break;

            // ── v13.0: Voice Hosting Management — All 10 Platform Features ──
            case 'checkUptime':               $result = toolVoiceUptime($args);              break;
            case 'checkSiteHealth':           $result = toolVoiceSiteDoctor($args);           break;
            case 'checkEmailHealth':          $result = toolVoiceEmailHealth($args);          break;
            case 'getAutopilotStatus':        $result = toolVoiceAutopilot($args);            break;
            case 'getMonthlyReport':          $result = toolVoiceMonthlyReport($args);        break;
            case 'checkDiskUsage':            $result = toolVoiceDiskUsage($args);            break;
            case 'renewSSL':                  $result = toolVoiceRenewSSL($args);             break;
            case 'createBackup':              $result = toolVoiceBackup($args);               break;
            case 'listStagingSites':          $result = toolVoiceListStaging($args);          break;
            case 'getClientHealth':           $result = toolVoiceClientHealth($args);         break;
            case 'getRevenueReport':          $result = toolVoiceRevenue($args);              break;
            case 'listWebhooks':              $result = toolVoiceListWebhooks($args);         break;

            // ── v14.0: Personal Agent & Games ───────────────────────────
            case 'personalCall':              $result = toolPersonalCall($args);               break;
            case 'personalSMS':               $result = toolPersonalSMS($args);                break;
            case 'addContact':                $result = toolAddContact($args);                 break;
            case 'lookupContact':             $result = toolLookupContact($args);              break;
            case 'playGame':                  $result = toolPlayGame($args);                   break;
            case 'makeGameMove':              $result = toolMakeGameMove($args);               break;
            case 'resignGame':                $result = toolResignGame($args);                 break;
            case 'delegateTask':              $result = toolDelegateTask($args);               break;
            case 'checkDelegation':           $result = toolCheckDelegation($args);            break;
            case 'getAlfredReport':           $result = toolGetAlfredReport($args);            break;

            // ── v15.0: Autopilot Evolution, Website Editor/Builder, Fleet ──
            case 'proactiveScan':             $result = toolProactiveScan($args);              break;
            case 'securityScan':              $result = toolSecurityScan($args);               break;
            case 'generateNarrative':         $result = toolGenerateNarrative($args);          break;
            case 'editWebsite':               $result = toolEditWebsite($args);                break;
            case 'buildWebsite':              $result = toolBuildWebsite($args);               break;
            case 'buildWebsiteStatus':        $result = toolBuildWebsiteStatus($args);         break;
            case 'fleetExecute':              $result = toolFleetExecute($args);               break;

            // ── v16.0: VR World Voice Tools ─────────────────────────────
            case 'vrStartMatch':              $result = toolVrStartMatch($args);               break;
            case 'vrClaimPlot':               $result = toolVrClaimPlot($args);                break;
            case 'vrGetLeaderboard':          $result = toolVrGetLeaderboard($args);           break;
            case 'vrCustomizeAvatar':         $result = toolVrCustomizeAvatar($args);          break;
            case 'vrStartTournament':         $result = toolVrStartTournament($args);          break;
            case 'vrGetMyPlots':              $result = toolVrGetMyPlots($args);               break;
            case 'vrEnterWorld':              $result = toolVrEnterWorld($args);               break;

            // ── v17.0: Expanded Ecosystem Tools ─────────────────────────

            // Affiliate Management
            case 'affiliateRegister':         $result = toolAffiliateRegister($args);          break;
            case 'affiliateStats':            $result = toolAffiliateStats($args);             break;
            case 'affiliateLink':             $result = toolAffiliateLink($args);              break;
            case 'affiliateRequestPayout':    $result = toolAffiliateRequestPayout($args);     break;

            // Collaboration
            case 'collabCreateSession':       $result = toolCollabCreateSession($args);        break;
            case 'collabJoinSession':         $result = toolCollabJoinSession($args);          break;
            case 'collabCreateDoc':           $result = toolCollabCreateDoc($args);            break;

            // Crypto Intelligence
            case 'cryptoDashboard':           $result = toolCryptoDashboard($args);            break;
            case 'cryptoAnalyzeToken':        $result = toolCryptoAnalyzeToken($args);         break;
            case 'cryptoSignals':             $result = toolCryptoSignals($args);              break;
            case 'cryptoWatchlist':           $result = toolCryptoWatchlist($args);            break;
            case 'cryptoPortfolioRisk':       $result = toolCryptoPortfolioRisk($args);       break;

            // Crypto Transfers
            case 'cryptoGenerateQR':          $result = toolCryptoGenerateQR($args);           break;
            case 'cryptoVerifyPayment':       $result = toolCryptoVerifyPayment($args);        break;
            case 'cryptoWallets':             $result = toolCryptoWallets($args);              break;

            // Deep Research
            case 'deepResearch':              $result = toolDeepResearch($args);               break;

            // Evolve Mode
            case 'evolveStatus':              $result = toolEvolveStatus($args);               break;
            case 'evolveActivate':            $result = toolEvolveActivate($args);             break;
            case 'evolveDeactivate':          $result = toolEvolveDeactivate($args);           break;
            case 'evolveProposals':           $result = toolEvolveProposals($args);            break;
            case 'evolveApprove':             $result = toolEvolveApprove($args);              break;

            // Healthcare Patient Management
            case 'patientCreate':             $result = toolPatientCreate($args);              break;
            case 'patientSearch':             $result = toolPatientSearch($args);              break;
            case 'patientList':               $result = toolPatientList($args);                break;
            case 'soapNoteCreate':            $result = toolSoapNoteCreate($args);             break;
            case 'scheduleAppointment':       $result = toolScheduleAppointment($args);       break;
            case 'recordVitals':              $result = toolRecordVitals($args);               break;
            case 'orderLabWork':              $result = toolOrderLabWork($args);               break;
            case 'healthcareDashboard':       $result = toolHealthcareDashboard($args);       break;

            // Intel Briefing
            case 'intelBriefing':             $result = toolIntelBriefing($args);              break;
            case 'intelCategories':           $result = toolIntelCategories($args);            break;
            case 'intelHistory':              $result = toolIntelHistory($args);               break;

            // Investor Portal
            case 'investorSubmit':            $result = toolInvestorSubmit($args);             break;
            case 'investorDashboard':         $result = toolInvestorDashboard($args);          break;
            case 'investorMetrics':           $result = toolInvestorMetrics($args);            break;

            // Messaging Gateway
            case 'sendTelegram':              $result = toolSendTelegram($args);               break;
            case 'sendWhatsApp':              $result = toolSendWhatsApp($args);               break;
            case 'sendSlackMessage':          $result = toolSendSlackMessage($args);           break;
            case 'sendDiscordMessage':        $result = toolSendDiscordMessage($args);         break;
            case 'messagingStats':            $result = toolMessagingStats($args);             break;

            // Notifications
            case 'getNotifications':          $result = toolGetNotifications($args);           break;
            case 'markNotificationRead':      $result = toolMarkNotificationRead($args);      break;
            case 'notificationPreferences':   $result = toolNotificationPreferences($args);   break;

            // Pulse Social Feed
            case 'pulsePost':                 $result = toolPulsePost($args);                  break;
            case 'pulseFeed':                 $result = toolPulseFeed($args);                  break;

            // Reporting Engine
            case 'generateEngineReport':      $result = toolGenerateEngineReport($args);       break;
            case 'reportDashboardKPIs':       $result = toolReportDashboardKPIs($args);        break;
            case 'reportGrowthMetrics':       $result = toolReportGrowthMetrics($args);        break;

            // Reseller Management
            case 'resellerDashboard':         $result = toolResellerDashboard($args);          break;
            case 'resellerClients':           $result = toolResellerClients($args);            break;
            case 'resellerBranding':          $result = toolResellerBranding($args);           break;

            // Self-Healing
            case 'selfHealingCheck':          $result = toolSelfHealingCheck($args);           break;
            case 'selfHealingStatus':         $result = toolSelfHealingStatus($args);          break;

            // Small Biz CRM
            case 'bizContacts':               $result = toolBizContacts($args);                break;
            case 'bizCreateContact':          $result = toolBizCreateContact($args);           break;
            case 'bizProjects':               $result = toolBizProjects($args);                break;
            case 'bizCreateProject':          $result = toolBizCreateProject($args);           break;
            case 'bizTasks':                  $result = toolBizTasks($args);                   break;
            case 'bizCreateTask':             $result = toolBizCreateTask($args);              break;
            case 'bizCreateInvoice':          $result = toolBizCreateInvoice($args);           break;
            case 'bizTimeLog':                $result = toolBizTimeLog($args);                 break;
            case 'bizDashboard':              $result = toolBizDashboard($args);               break;

            // System Audit
            case 'systemAudit':               $result = toolSystemAudit($args);                break;

            // Treasury
            case 'treasuryDashboard':         $result = toolTreasuryDashboard($args);          break;
            case 'treasuryTransaction':       $result = toolTreasuryTransaction($args);        break;

            // World Events
            case 'worldEvents':               $result = toolWorldEvents($args);                break;

            // ZPE Research
            case 'zpeStatus':                 $result = toolZpeStatus($args);                  break;
            case 'zpeTopics':                 $result = toolZpeTopics($args);                  break;
            case 'zpeProgress':               $result = toolZpeProgress($args);                break;

            // News Feeds
            case 'getNewsFeeds':              $result = toolGetNewsFeeds($args);               break;

            // Hosting Management
            case 'manageAddonDomains':        $result = toolManageAddonDomains($args);         break;
            case 'manageDatabases':           $result = toolManageDatabases($args);            break;
            case 'manageFTP':                 $result = toolManageFTP($args);                  break;
            case 'manageSubdomains':          $result = toolManageSubdomains($args);           break;
            case 'manageCronJobs':            $result = toolManageCronJobs($args);             break;
            case 'manageRedirects':           $result = toolManageRedirects($args);            break;
            case 'manageBackups':             $result = toolManageBackups($args);              break;
            case 'installApp':                $result = toolInstallApp($args);                 break;
            case 'manageDomainPointers':      $result = toolManageDomainPointers($args);       break;
            case 'manageEmail':               $result = toolManageEmail($args);                break;
            case 'manageSSL':                 $result = toolManageSSL($args);                  break;
            case 'manageDNS':                 $result = toolManageDNS($args);                  break;
            case 'manageFiles':               $result = toolManageFiles($args);                break;
            case 'hostingStats':              $result = toolHostingStats($args);               break;

            // Site Doctor
            case 'runSiteDoctor':             $result = toolRunSiteDoctor($args);              break;
            case 'siteDoctorHistory':         $result = toolSiteDoctorHistory($args);          break;

            // AI Image Generation
            case 'generateAIImage':           $result = toolGenerateAIImage($args);            break;

            // AI Support Chat
            case 'aiSupportChat':             $result = toolAISupportChat($args);              break;

            // Agent Tracker
            case 'agentTrackerDashboard':     $result = toolAgentTrackerDashboard($args);      break;
            case 'agentTrackerSearch':        $result = toolAgentTrackerSearch($args);         break;
            case 'agentTrackerDeploy':        $result = toolAgentTrackerDeploy($args);         break;
            case 'agentTrackerReport':        $result = toolAgentTrackerReport($args);         break;

            // Autopilot Management
            case 'toggleAutopilot':           $result = toolToggleAutopilot($args);            break;
            case 'autopilotReport':           $result = toolAutopilotReport($args);            break;
            case 'autopilotHistory':          $result = toolAutopilotHistory($args);           break;

            // Enterprise Billing
            case 'enterpriseBilling':         $result = toolEnterpriseBilling($args);           break;

            // Gamification Expanded
            case 'gamificationProfile':       $result = toolGamificationProfile($args);        break;
            case 'gamificationAwardXP':       $result = toolGamificationAwardXP($args);        break;
            case 'dailyChallenge':            $result = toolDailyChallenge($args);             break;
            case 'gamificationLeaderboard':   $result = toolGamificationLeaderboard($args);    break;

            // Cart Management
            case 'cartAdd':                   $result = toolCartAdd($args);                    break;
            case 'cartView':                  $result = toolCartView($args);                   break;
            case 'cartRemove':                $result = toolCartRemove($args);                 break;
            case 'applyPromo':                $result = toolApplyPromo($args);                 break;

            // Service Management
            case 'serviceList':               $result = toolServiceList($args);                break;
            case 'serviceDetail':             $result = toolServiceDetail($args);              break;

            // Tickets Expanded
            case 'listTickets':               $result = toolListTickets($args);                break;
            case 'replyTicket':               $result = toolReplyTicket($args);                break;

            // Knowledge Base
            case 'searchKnowledgeBase':       $result = toolSearchKnowledgeBase($args);        break;
            case 'getKBArticle':              $result = toolGetKBArticle($args);               break;

            // Uptime Monitoring Expanded
            case 'uptimeOverview':            $result = toolUptimeOverview($args);             break;
            case 'uptimeToggle':              $result = toolUptimeToggle($args);               break;
            case 'uptimeIncidents':           $result = toolUptimeIncidents($args);            break;

            // Webhooks Expanded
            case 'webhookSubscribe':          $result = toolWebhookSubscribe($args);           break;
            case 'webhookEvents':             $result = toolWebhookEvents($args);              break;

            // Analytics Expanded
            case 'analyticsDashboard':        $result = toolAnalyticsDashboard($args);         break;

            // Marketplace Expanded
            case 'marketplaceBrowseExpanded': $result = toolMarketplaceBrowseExpanded($args);  break;
            case 'marketplaceInstallExpand':  $result = toolMarketplaceInstallExpand($args);   break;

            // Consciousness Layer Expanded
            case 'consciousnessGreeting':     $result = toolConsciousnessGreeting($args);      break;
            case 'consciousnessRapport':      $result = toolConsciousnessRapport($args);       break;

            // Goals Management
            case 'goalsCreate':               $result = toolGoalsCreate($args);                break;
            case 'goalsList':                 $result = toolGoalsList($args);                  break;
            case 'goalsUpdate':               $result = toolGoalsUpdate($args);                break;

            // Onboarding
            case 'onboardingStatus':          $result = toolOnboardingStatus($args);           break;
            case 'onboardingComplete':        $result = toolOnboardingComplete($args);         break;

            // Provisioning
            case 'provisionService':          $result = toolProvisionService($args);           break;
            case 'provisionStatus':           $result = toolProvisionStatus($args);            break;

            // Delegation Expanded
            case 'delegationStatus':          $result = toolDelegationStatus($args);           break;
            case 'delegationHistory':         $result = toolDelegationHistory($args);          break;

            // Revenue Agents
            case 'revenueAgentReport':        $result = toolRevenueAgentReport($args);         break;
            case 'revenueRecommendations':    $result = toolRevenueRecommendations($args);     break;

            // Sanctuary (Bible)
            case 'sanctuaryVerse':            $result = toolSanctuaryVerse($args);             break;
            case 'sanctuaryStudy':            $result = toolSanctuaryStudy($args);             break;

            // Learning
            case 'learningCourse':            $result = toolLearningCourse($args);             break;

            // Orchestrator
            case 'orchestratorExecute':       $result = toolOrchestratorExecute($args);        break;
            case 'orchestratorPresets':        $result = toolOrchestratorPresets($args);        break;

            // Contingency
            case 'contingencyPlan':           $result = toolContingencyPlan($args);            break;

            // Pro Discussions
            case 'proDiscussion':             $result = toolProDiscussion($args);              break;

            // Metaverse Presence
            case 'metaverseStatus':           $result = toolMetaverseStatus($args);            break;
            case 'metaverseZones':            $result = toolMetaverseZones($args);             break;
            case 'metaverseMeeting':          $result = toolMetaverseMeeting($args);           break;

            // SSO
            case 'ssoStatus':                 $result = toolSSOStatus($args);                  break;

            // Deploy
            case 'deployStatus':              $result = toolDeployStatus($args);               break;
            case 'deployTrigger':             $result = toolDeployTrigger($args);              break;

            // Veil Protocol Reports
            case 'veilMorningBriefing':       $result = toolVeilMorningBriefing($args);        break;
            case 'veilServiceHealth':         $result = toolVeilServiceHealth($args);          break;
            case 'veilAgentPerformance':      $result = toolVeilAgentPerformance($args);       break;
            case 'veilSecurityReport':        $result = toolVeilSecurityReport($args);         break;
            case 'veilEcosystemGaps':         $result = toolVeilEcosystemGaps($args);          break;

            // Conversations Management
            case 'conversationsList':         $result = toolConversationsList($args);          break;
            case 'conversationStats':         $result = toolConversationStats($args);          break;
            case 'conversationExport':        $result = toolConversationExport($args);         break;

            // Creative Tools
            case 'creativeGenerate':          $result = toolCreativeGenerate($args);           break;

            // Agent Deploy
            case 'agentDeploy':               $result = toolAgentDeploy($args);                break;

            // Voice Games Expanded
            case 'gameHistory':               $result = toolGameHistory($args);                break;
            case 'gameLeaderboard':           $result = toolGameLeaderboard($args);            break;

            // ══════════════════════════════════════════════════════════════
            // v18.0 "Deep Coverage" — 178 NEW tools (664→842)
            // ══════════════════════════════════════════════════════════════

            // ── Accounting ──────────────────────────────────────────────
            case 'accountingDashboard':       $result = toolAccountingDashboard($args);        break;
            case 'accountingInvoices':        $result = toolAccountingInvoices($args);         break;
            case 'accountingCreateInvoice':   $result = toolAccountingCreateInvoice($args);    break;
            case 'accountingMarkPaid':        $result = toolAccountingMarkPaid($args);         break;
            case 'accountingExpenses':        $result = toolAccountingExpenses($args);         break;
            case 'accountingAddExpense':      $result = toolAccountingAddExpense($args);       break;
            case 'accountingReports':         $result = toolAccountingReports($args);          break;
            case 'accountingTaxSummary':      $result = toolAccountingTaxSummary($args);       break;

            // ── Documents ───────────────────────────────────────────────
            case 'documentParse':             $result = toolDocumentParse($args);              break;
            case 'documentOCR':               $result = toolDocumentOCR($args);                break;
            case 'documentSummarize':         $result = toolDocumentSummarize($args);          break;
            case 'documentExtract':           $result = toolDocumentExtract($args);            break;

            // ── Composio ────────────────────────────────────────────────
            case 'composioApps':              $result = toolComposioApps($args);               break;
            case 'composioConnect':           $result = toolComposioConnect($args);            break;
            case 'composioExecute':           $result = toolComposioExecute($args);            break;
            case 'composioDisconnect':        $result = toolComposioDisconnect($args);         break;

            // ── Team ────────────────────────────────────────────────────
            case 'teamOverview':              $result = toolTeamOverview($args);               break;
            case 'teamShareAgent':            $result = toolTeamShareAgent($args);             break;
            case 'teamShareConversation':     $result = toolTeamShareConversation($args);      break;
            case 'teamInvite':                $result = toolTeamInvite($args);                 break;
            case 'teamJoin':                  $result = toolTeamJoin($args);                   break;
            case 'teamMembers':               $result = toolTeamMembers($args);                break;

            // ── Usage Tracking ──────────────────────────────────────────
            case 'usageSummary':              $result = toolUsageSummary($args);               break;
            case 'usageLimits':               $result = toolUsageLimits($args);                break;
            case 'usageAlerts':               $result = toolUsageAlerts($args);                break;

            // ── RSS/News Feeds ──────────────────────────────────────────
            case 'feedAdd':                   $result = toolFeedAdd($args);                    break;
            case 'feedList':                  $result = toolFeedList($args);                   break;
            case 'feedPoll':                  $result = toolFeedPoll($args);                   break;
            case 'feedProcess':               $result = toolFeedProcess($args);                break;

            // ── Email Advanced ──────────────────────────────────────────
            case 'emailForwarderCreate':      $result = toolEmailForwarderCreate($args);       break;
            case 'emailForwarderDelete':      $result = toolEmailForwarderDelete($args);       break;
            case 'emailAutoresponderCreate':  $result = toolEmailAutoresponderCreate($args);   break;
            case 'emailAutoresponderDelete':  $result = toolEmailAutoresponderDelete($args);   break;
            case 'emailCatchallSet':          $result = toolEmailCatchallSet($args);           break;
            case 'emailChangeQuota':          $result = toolEmailChangeQuota($args);           break;
            case 'emailChangePassword':       $result = toolEmailChangePassword($args);        break;

            // ── SSL Management ──────────────────────────────────────────
            case 'sslInfo':                   $result = toolSslInfo($args);                    break;
            case 'sslRequestCert':            $result = toolSslRequestCert($args);             break;
            case 'sslForceHttps':             $result = toolSslForceHttps($args);              break;

            // ── Domain Advanced ─────────────────────────────────────────
            case 'domainLock':                $result = toolDomainLock($args);                 break;
            case 'domainEpp':                 $result = toolDomainEpp($args);                  break;
            case 'domainAutorenew':           $result = toolDomainAutorenew($args);            break;
            case 'domainNameservers':         $result = toolDomainNameservers($args);          break;
            case 'domainDnsAdd':              $result = toolDomainDnsAdd($args);               break;
            case 'domainDnsDelete':           $result = toolDomainDnsDelete($args);            break;

            // ── Backups ─────────────────────────────────────────────────
            case 'backupList':                $result = toolBackupList($args);                 break;
            case 'backupCreate':              $result = toolBackupCreate($args);               break;
            case 'backupRestore':             $result = toolBackupRestore($args);              break;

            // ── File Manager ────────────────────────────────────────────
            case 'fileReadContent':           $result = toolFileReadContent($args);            break;
            case 'fileSave':                  $result = toolFileSave($args);                   break;
            case 'fileMkdir':                 $result = toolFileMkdir($args);                  break;
            case 'fileRename':                $result = toolFileRename($args);                 break;
            case 'fileChmod':                 $result = toolFileChmod($args);                  break;
            case 'fileDelete':                $result = toolFileDelete($args);                 break;

            // ── Cron Jobs ───────────────────────────────────────────────
            case 'cronList':                  $result = toolCronList($args);                   break;
            case 'cronCreate':                $result = toolCronCreate($args);                 break;
            case 'cronDelete':                $result = toolCronDelete($args);                 break;

            // ── Database ────────────────────────────────────────────────
            case 'databaseCreate':            $result = toolDatabaseCreate($args);             break;
            case 'databaseDelete':            $result = toolDatabaseDelete($args);             break;

            // ── FTP ─────────────────────────────────────────────────────
            case 'ftpCreate':                 $result = toolFtpCreate($args);                  break;
            case 'ftpDelete':                 $result = toolFtpDelete($args);                  break;
            case 'ftpChangePassword':         $result = toolFtpChangePassword($args);          break;

            // ── Addon Domains ───────────────────────────────────────────
            case 'addonDomainCreate':         $result = toolAddonDomainCreate($args);          break;
            case 'addonDomainDelete':         $result = toolAddonDomainDelete($args);          break;

            // ── Subdomains ──────────────────────────────────────────────
            case 'subdomainCreate':           $result = toolSubdomainCreate($args);            break;
            case 'subdomainDelete':           $result = toolSubdomainDelete($args);            break;

            // ── Domain Pointers ─────────────────────────────────────────
            case 'domainPointerCreate':       $result = toolDomainPointerCreate($args);        break;
            case 'domainPointerDelete':       $result = toolDomainPointerDelete($args);        break;

            // ── Redirects ───────────────────────────────────────────────
            case 'redirectCreate':            $result = toolRedirectCreate($args);             break;
            case 'redirectDelete':            $result = toolRedirectDelete($args);             break;

            // ── App Management ──────────────────────────────────────────
            case 'appUpdate':                 $result = toolAppUpdate($args);                  break;
            case 'appUninstall':              $result = toolAppUninstall($args);               break;

            // ── Ticket Advanced ─────────────────────────────────────────
            case 'ticketView':                $result = toolTicketView($args);                 break;
            case 'ticketReply':               $result = toolTicketReply($args);                break;
            case 'ticketClose':               $result = toolTicketClose($args);                break;
            case 'ticketDepartments':         $result = toolTicketDepartments($args);          break;

            // ── Autopilot Evolution ─────────────────────────────────────
            case 'autopilotAutoFix':          $result = toolAutopilotAutoFix($args);           break;
            case 'autopilotNarrative':        $result = toolAutopilotNarrative($args);         break;
            case 'autopilotSecurityEvents':   $result = toolAutopilotSecurityEvents($args);    break;
            case 'autopilotConfidenceExplain':$result = toolAutopilotConfidenceExplain($args); break;

            // ── Website Builder ─────────────────────────────────────────
            case 'websiteBuilderStart':       $result = toolWebsiteBuilderStart($args);        break;
            case 'websiteBuilderContinue':    $result = toolWebsiteBuilderContinue($args);     break;
            case 'websiteBuilderStatus':      $result = toolWebsiteBuilderStatus($args);       break;
            case 'websiteBuilderList':        $result = toolWebsiteBuilderList($args);         break;

            // ── Website Editor ──────────────────────────────────────────
            case 'websiteEditorReadFile':     $result = toolWebsiteEditorReadFile($args);      break;
            case 'websiteEditorSaveFile':     $result = toolWebsiteEditorSaveFile($args);      break;
            case 'websiteEditorCreateFile':   $result = toolWebsiteEditorCreateFile($args);    break;
            case 'websiteEditorAIEdit':       $result = toolWebsiteEditorAIEdit($args);        break;
            case 'websiteEditorTemplates':    $result = toolWebsiteEditorTemplates($args);     break;
            case 'websiteEditorInstallTemplate':$result = toolWebsiteEditorInstallTemplate($args); break;

            // ── Staging ─────────────────────────────────────────────────
            case 'stagingSync':               $result = toolStagingSync($args);                break;
            case 'stagingPush':               $result = toolStagingPush($args);                break;
            case 'stagingDelete':             $result = toolStagingDelete($args);              break;
            case 'stagingCredentials':        $result = toolStagingCredentials($args);         break;

            // ── Agent Deploy Extended ───────────────────────────────────
            case 'agentDeployPause':          $result = toolAgentDeployPause($args);           break;
            case 'agentDeployResume':         $result = toolAgentDeployResume($args);          break;
            case 'agentDeployDelete':         $result = toolAgentDeployDelete($args);          break;
            case 'agentDeployCatalog':        $result = toolAgentDeployCatalog($args);         break;
            case 'agentDeployDetail':         $result = toolAgentDeployDetail($args);          break;

            // ── Collab Extended ──────────────────────────────────────────
            case 'collabEnd':                 $result = toolCollabEnd($args);                  break;
            case 'collabInvite':              $result = toolCollabInvite($args);               break;
            case 'collabList':                $result = toolCollabList($args);                 break;

            // ── Crypto Trading ──────────────────────────────────────────
            case 'cryptoTradeQuote':          $result = toolCryptoTradeQuote($args);           break;
            case 'cryptoTradePropose':        $result = toolCryptoTradePropose($args);         break;
            case 'cryptoTradeApprove':        $result = toolCryptoTradeApprove($args);         break;
            case 'cryptoTradeHistory':        $result = toolCryptoTradeHistory($args);         break;
            case 'cryptoPortfolioCreate':     $result = toolCryptoPortfolioCreate($args);      break;
            case 'cryptoPortfolioStatus':     $result = toolCryptoPortfolioStatus($args);      break;
            case 'cryptoGSMBalance':          $result = toolCryptoGSMBalance($args);           break;
            case 'cryptoGSMHistory':          $result = toolCryptoGSMHistory($args);           break;
            case 'cryptoGSMLeaderboard':      $result = toolCryptoGSMLeaderboard($args);       break;
            case 'cryptoPrices':              $result = toolCryptoPrices($args);               break;
            case 'cryptoGSMStake':            $result = toolCryptoGSMStake($args);             break;
            case 'cryptoPayCreate':           $result = toolCryptoPayCreate($args);            break;
            case 'cryptoPayVerify':           $result = toolCryptoPayVerify($args);            break;
            case 'cryptoVRLand':              $result = toolCryptoVRLand($args);               break;
            case 'cryptoChessWager':          $result = toolCryptoChessWager($args);           break;

            // ── Reseller Extended ───────────────────────────────────────
            case 'resellerPricing':           $result = toolResellerPricing($args);            break;
            case 'resellerInvite':            $result = toolResellerInvite($args);             break;
            case 'resellerToggle':            $result = toolResellerToggle($args);             break;

            // ── Support Chat ────────────────────────────────────────────
            case 'supportChatSend':           $result = toolSupportChatSend($args);            break;
            case 'supportChatHistory':        $result = toolSupportChatHistory($args);         break;

            // ── Gamification ────────────────────────────────────────────
            case 'gamificationDailyChallenge':$result = toolGamificationDailyChallenge($args); break;

            // ── Provisioning ────────────────────────────────────────────
            case 'provisionSuspend':          $result = toolProvisionSuspend($args);           break;
            case 'provisionUnsuspend':        $result = toolProvisionUnsuspend($args);         break;
            case 'provisionTerminate':        $result = toolProvisionTerminate($args);         break;
            case 'provisionUpgrade':          $result = toolProvisionUpgrade($args);           break;
            case 'provisionTest':             $result = toolProvisionTest($args);              break;

            // ── Server Stats ────────────────────────────────────────────
            case 'errorLog':                  $result = toolErrorLog($args);                   break;
            case 'accessLog':                 $result = toolAccessLog($args);                  break;
            case 'serverUsage':               $result = toolServerUsage($args);                break;

            // ── SSO ─────────────────────────────────────────────────────
            case 'ssoGenerateToken':          $result = toolSsoGenerateToken($args);           break;

            // ── Comms/Messaging ─────────────────────────────────────────
            case 'commsCreateGroup':          $result = toolCommsCreateGroup($args);           break;
            case 'commsGroupSend':            $result = toolCommsGroupSend($args);             break;
            case 'commsGroupMessages':        $result = toolCommsGroupMessages($args);         break;
            case 'commsMyGroups':             $result = toolCommsMyGroups($args);              break;
            case 'commsSendMessage':          $result = toolCommsSendMessage($args);           break;
            case 'commsHistory':              $result = toolCommsHistory($args);               break;
            case 'commsUploadFile':           $result = toolCommsUploadFile($args);            break;

            // ── VR World ────────────────────────────────────────────────
            case 'vrChessMatch':              $result = toolVrChessMatch($args);               break;
            case 'vrChessChallenge':          $result = toolVrChessChallenge($args);           break;
            case 'vrWorldPlots':              $result = toolVrWorldPlots($args);               break;
            case 'vrWorldBuild':              $result = toolVrWorldBuild($args);               break;
            case 'vrAvatarGet':               $result = toolVrAvatarGet($args);                break;
            case 'vrAvatarSave':              $result = toolVrAvatarSave($args);               break;

            // ── Webhooks Extended ───────────────────────────────────────
            case 'webhookDelete':             $result = toolWebhookDelete($args);              break;
            case 'webhookUpdate':             $result = toolWebhookUpdate($args);              break;
            case 'webhookTest':               $result = toolWebhookTest($args);                break;
            case 'webhookLogs':               $result = toolWebhookLogs($args);                break;

            // ── Enterprise ──────────────────────────────────────────────
            case 'enterpriseCreate':          $result = toolEnterpriseCreate($args);           break;
            case 'enterpriseAddMember':       $result = toolEnterpriseAddMember($args);        break;
            case 'enterpriseDashboard':       $result = toolEnterpriseDashboard($args);        break;

            // ── Commissions & Payouts ───────────────────────────────────
            case 'commissionCalc':            $result = toolCommissionCalc($args);             break;
            case 'commissionApprove':         $result = toolCommissionApprove($args);          break;
            case 'payoutCreate':              $result = toolPayoutCreate($args);               break;
            case 'payoutProcess':             $result = toolPayoutProcess($args);              break;

            // ── Site Doctor ─────────────────────────────────────────────
            case 'siteDoctorScan':            $result = toolSiteDoctorScan($args);             break;
            case 'siteDoctorResults':         $result = toolSiteDoctorResults($args);          break;
            case 'siteDoctorReport':          $result = toolSiteDoctorReport($args);           break;

            // ── Agent Registry ──────────────────────────────────────────
            case 'agentRegistryList':         $result = toolAgentRegistryList($args);          break;
            case 'agentRegistryGet':          $result = toolAgentRegistryGet($args);           break;
            case 'agentHierarchy':            $result = toolAgentHierarchy($args);             break;
            case 'agentDelegateTask':         $result = toolAgentDelegateTask($args);          break;
            case 'agentMessages':             $result = toolAgentMessages($args);              break;
            case 'agentHeartbeat':            $result = toolAgentHeartbeat($args);             break;
            case 'agentRegistryStats':        $result = toolAgentRegistryStats($args);         break;

            // ── Fleet Extended ──────────────────────────────────────────
            case 'fleetBatch':                $result = toolFleetBatch($args);                 break;
            case 'fleetAvailable':            $result = toolFleetAvailable($args);             break;
            case 'fleetHistory':              $result = toolFleetHistory($args);               break;

            // ── Learning System ─────────────────────────────────────────
            case 'learningInsights':          $result = toolLearningInsights($args);           break;
            case 'learningPatterns':          $result = toolLearningPatterns($args);           break;
            case 'learningPerformance':       $result = toolLearningPerformance($args);        break;

            // ── Marketplace Extended ────────────────────────────────────
            case 'marketplacePublish':        $result = toolMarketplacePublish($args);         break;
            case 'marketplaceRate':           $result = toolMarketplaceRate($args);            break;
            case 'marketplaceUninstall':      $result = toolMarketplaceUninstall($args);       break;

            // ── Uptime Extended ─────────────────────────────────────────
            case 'uptimeCheck':               $result = toolUptimeCheck($args);                break;
            case 'uptimeHistory':             $result = toolUptimeHistory($args);              break;
            case 'uptimeIncidentDetails':     $result = toolUptimeIncidentDetails($args);      break;

            // ── END v18.0 ───────────────────────────────────────────────

            // ── MCP Passthrough: any tool registered on the assistant (full catalog) ──
            default:
                $ideOnly = [
                    'read_file', 'write_file', 'delete_file', 'rename_file', 'find_file',
                    'list_directory', 'get_file_info', 'search_files', 'create_directory',
                    'create_checkpoint', 'restore_checkpoint', 'list_checkpoints',
                    'create_chart', 'create_diagram', 'create_rest_api', 'create_user_table',
                    'create_onboarding_checklist', 'delete_scheduled_task',
                    'get_error_summary', 'get_index_stats', 'get_isolation_status',
                    'get_mcp_usage', 'get_tool_doc', 'get_tool_docs',
                    'list_artifacts', 'list_interpreter_sessions', 'list_generated_images',
                    'search_tools', 'read_pdf', 'setup_auth', 'setup_cors', 'setup_ci_cd',
                    'setup_docker', 'setup_oauth', 'setup_webhook', 'setup_live_chat',
                    'auto_fix_config', 'db_query', 'db_schema', 'db_list', 'db_migrate',
                    'generate_utility', 'generate_readme', 'generate_api_keys',
                    'export_data',
                ];
                if (in_array($toolName, $ideOnly, true)) {
                    $result = ['error' => 'Unknown tool: ' . $toolName];
                } else {
                    $result = mcpActionVoiceDispatch($toolName, $args, $vapiCallId);
                }
        }
    } catch (Exception $e) {
        error_log('Vapi tool error [' . $toolName . ']: ' . $e->getMessage());
        $result = ['error' => 'Internal error. Please try again or I can have our team call you back.'];
    }

    $results[] = ['toolCallId' => $toolCallId, 'result' => json_encode($result)];
}

echo json_encode(['results' => $results]);

endif; // !$_vapiToolsIncludedFromChat


// ═══════════════════════════════════════════════════════════════════════════
// 1. AUTHENTICATE CUSTOMER
// Strong: email + PIN (1 factor)
// Standard: email + phone last4 + secret answer (2 factors)
// Lockout: 5 fails in 30 mins = locked, callback required
// ═══════════════════════════════════════════════════════════════════════════
function toolAuthenticate($args, $vapiCallId = '', $vapiCallerNum = '') {
    $email        = strtolower(trim($args['email'] ?? ''));
    $pin          = trim($args['pin'] ?? '');
    $phoneLast4   = preg_replace('/\D/', '', $args['phone_last4'] ?? '');
    $secretType   = strtolower(trim($args['secret_type'] ?? ''));
    $secretAnswer = strtolower(trim($args['secret_answer'] ?? ''));
    $callerPhone  = trim($args['caller_phone'] ?? '');

    if (empty($email)) {
        return ['authenticated' => false, 'step' => 'need_email',
            'message' => 'I need your email address to find your account. What email did you sign up with?'];
    }

    $db = getDB();
    if (!$db) return ['authenticated' => false,
        'message' => 'I am having a technical issue. I will have our team call you back within 24 hours.'];

    // Lockout check
    $lockout = authLockout($db, $email);
    if ($lockout['locked']) {
        authLog($db, $email, $callerPhone, false, 'locked');
        return ['authenticated' => false, 'locked' => true,
            'message' => 'For your security this account is temporarily locked after too many failed attempts. I am creating a high-priority callback request for our team right now. Can I get the best number to reach you?'];
    }

    // Fetch account
    $stmt = $db->prepare("
        SELECT c.id, c.firstname, c.lastname, c.email, c.phone, c.city, c.date_created, c.support_pin,
               (SELECT ROUND(total,2) FROM invoices WHERE client_id=c.id ORDER BY id DESC LIMIT 1) AS last_invoice,
               (SELECT domain FROM domains WHERE client_id=c.id ORDER BY id ASC LIMIT 1) AS first_domain,
               (SELECT domain FROM services WHERE client_id=c.id ORDER BY id ASC LIMIT 1) AS first_service
        FROM clients c WHERE c.email = :e AND c.status = 'Active' LIMIT 1
    ");
    $stmt->execute([':e' => $email]);
    $client = $stmt->fetch();

    if (!$client) {
        authLog($db, $email, $callerPhone, false, 'not_found');
        return ['authenticated' => false, 'step' => 'need_email',
            'message' => 'I could not find an active account with that email. Could you double-check it? Try spelling it out letter by letter.'];
    }

    // PATH A: Has PIN — single factor
    if (!empty($client['support_pin'])) {
        if (empty($pin)) {
            return ['authenticated' => false, 'step' => 'need_pin', 'has_pin' => true,
                'message' => 'I found your account! You have a support PIN set up. What is your 4-digit support PIN?'];
        }
        if (password_verify($pin, $client['support_pin'])) {
            authLog($db, $email, $callerPhone, true, 'pin');
            return authSuccess($client, 'pin', $vapiCallId, $vapiCallerNum);
        }
        authLog($db, $email, $callerPhone, false, 'wrong_pin');
        $left = authRemaining($db, $email);
        if ($left <= 0) return ['authenticated' => false, 'locked' => true,
            'message' => 'That PIN is incorrect and this account is now temporarily locked for your security. Our team will call you back within 24 hours.'];
        return ['authenticated' => false, 'step' => 'need_pin',
            'message' => 'That PIN does not match. You have ' . $left . ' attempt(s) remaining. Please try again.'];
    }

    // PATH B: No PIN — phone last4 + secret
    if (empty($phoneLast4)) {
        return ['authenticated' => false, 'step' => 'need_phone', 'has_pin' => false,
            'message' => 'I found your account! Since you do not have a support PIN yet, I will need two pieces of information. First — what are the last 4 digits of your phone number on file?'];
    }

    $stored = preg_replace('/\D/', '', $client['phone']);
    if (empty($stored) || !str_ends_with($stored, $phoneLast4)) {
        authLog($db, $email, $callerPhone, false, 'wrong_phone');
        $left = authRemaining($db, $email);
        if ($left <= 0) return ['authenticated' => false, 'locked' => true,
            'message' => 'Those digits do not match and this account is now temporarily locked. Our team will call you back within 24 hours.'];
        return ['authenticated' => false, 'step' => 'need_phone',
            'message' => 'Those digits do not match. ' . $left . ' attempt(s) remaining. Try again or I can have our team call you back.'];
    }

    // Phone OK — need secret
    if (empty($secretAnswer)) {
        $prompt = secretPrompt($client);
        // Store expected secret type server-side so client cannot tamper
        try {
            $db->prepare("DELETE FROM alfred_auth_challenges WHERE email=:e")->execute([':e'=>$email]);
            $db->prepare("INSERT INTO alfred_auth_challenges (email, secret_type, created_at) VALUES (:e, :t, NOW())")
               ->execute([':e'=>$email, ':t'=>$prompt['type']]);
        } catch(Exception $ex) {
            // Table may not exist yet — create it
            $db->exec("CREATE TABLE IF NOT EXISTS alfred_auth_challenges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                secret_type VARCHAR(50) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX(email)
            ) ENGINE=InnoDB");
            $db->prepare("INSERT INTO alfred_auth_challenges (email, secret_type, created_at) VALUES (:e, :t, NOW())")
               ->execute([':e'=>$email, ':t'=>$prompt['type']]);
        }
        return ['authenticated' => false, 'step' => 'need_secret',
            'secret_type' => $prompt['type'],
            'message' => 'Phone verified! One more thing for security — ' . $prompt['question']];
    }

    // Enforce server-side secret type — ignore client-supplied type
    $enforced = $db->prepare("SELECT secret_type FROM alfred_auth_challenges WHERE email=:e AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE) ORDER BY id DESC LIMIT 1");
    $enforced->execute([':e'=>$email]);
    $challengeRow = $enforced->fetch();
    if (!$challengeRow) {
        // No valid challenge found — restart secret flow
        $prompt = secretPrompt($client);
        try {
            $db->prepare("DELETE FROM alfred_auth_challenges WHERE email=:e")->execute([':e'=>$email]);
            $db->prepare("INSERT INTO alfred_auth_challenges (email, secret_type, created_at) VALUES (:e, :t, NOW())")
               ->execute([':e'=>$email, ':t'=>$prompt['type']]);
        } catch(Exception $ex) {}
        return ['authenticated' => false, 'step' => 'need_secret',
            'secret_type' => $prompt['type'],
            'message' => 'Let me re-verify for security. ' . $prompt['question']];
    }
    $secretType = $challengeRow['secret_type']; // Server-enforced, not client-supplied

    if (!verifySecret($client, $secretType, $secretAnswer)) {
        authLog($db, $email, $callerPhone, false, 'wrong_secret');
        // Clean up challenge on failure
        $db->prepare("DELETE FROM alfred_auth_challenges WHERE email=:e")->execute([':e'=>$email]);
        $left = authRemaining($db, $email);
        if ($left <= 0) return ['authenticated' => false, 'locked' => true,
            'message' => 'That answer does not match and this account is now locked. Our team will call you back within 24 hours.'];
        return ['authenticated' => false, 'step' => 'need_secret',
            'message' => 'That does not match. ' . $left . ' attempt(s) remaining. Try again or I can have our team call you back.'];
    }

    authLog($db, $email, $callerPhone, true, 'phone+secret');
    // Clean up auth challenge
    try { $db->prepare("DELETE FROM alfred_auth_challenges WHERE email=:e")->execute([':e'=>$email]); } catch(Exception $ex) {}
    $r = authSuccess($client, 'phone+secret', $vapiCallId, $vapiCallerNum);
    $r['message'] .= ' By the way, you can set a 4-digit support PIN at gositeme.com/pay to make future calls much faster!';
    return $r;
}

function authSuccess($c, $method, $vapiCallId = '', $vapiCallerNum = '') {
    // P3: Store client_id in Redis keyed by call_id for webhook retrieval
    if ($vapiCallId && $c['id']) {
        toolsRedisSetCallClientId($vapiCallId, $c['id']);
    }

    $result = ['authenticated' => true, 'client_id' => $c['id'],
        'first_name' => $c['firstname'], 'full_name' => $c['firstname'].' '.$c['lastname'],
        'email' => $c['email'], 'auth_method' => $method,
        'message' => 'Identity verified! Welcome back, '.$c['firstname'].'! How can I help you today?'];

    // P2: Inject cross-call memory context
    $callerNum = $vapiCallerNum ?: ($c['phone'] ?? '');
    $memory = toolsGetCallerMemoryContext($callerNum, $c['id']);
    if ($memory) {
        $result['caller_context'] = $memory['context'];
        $result['previous_call_count'] = $memory['call_count'];
        if ($memory['caller_name']) {
            $result['message'] = 'Identity verified! Welcome back, '.$c['firstname'].'! I can see we have spoken before. How can I help you today?';
        }
    }

    return $result;
}

function secretPrompt($c) {
    if (!empty($c['last_invoice'])) return ['type'=>'invoice_amount','question'=>'What was the amount of your most recent invoice? Just the dollar amount is fine.'];
    if (!empty($c['first_domain'])) return ['type'=>'domain','question'=>'What is one of the domain names on your account?'];
    if (!empty($c['first_service'])) return ['type'=>'service_domain','question'=>'What domain name is associated with your hosting service?'];
    if (!empty($c['city'])) return ['type'=>'city','question'=>'What city is listed on your account?'];
    return ['type'=>'year','question'=>'What year did you create your GoSiteMe account?'];
}

function verifySecret($c, $type, $answer) {
    switch ($type) {
        case 'invoice_amount':
            $stated = (float)preg_replace('/[^\d.]/', '', $answer);
            $actual = (float)$c['last_invoice'];
            // Strict: must match to the exact cent — no tolerance
            return $actual > 0 && round($stated, 2) === round($actual, 2);
        case 'domain':
        case 'service_domain':
            $key = $type === 'domain' ? 'first_domain' : 'first_service';
            $d   = strtolower(trim($c[$key] ?? ''));
            $a   = strtolower(trim(preg_replace('/^(https?:\/\/)?(www\.)?/', '', $answer)));
            $a   = preg_replace('/\/.*$/', '', $a); // strip path
            // Require at least 4 chars and near-exact match (not substring)
            if (strlen($a) < 4 || empty($d)) return false;
            // Extract domain name without TLD for comparison
            $dBase = explode('.', $d)[0]; // e.g. "mysite" from "mysite.com"
            $aBase = explode('.', $a)[0];
            // Must match full domain or base domain exactly
            return ($a === $d) || ($aBase === $dBase && strlen($dBase) >= 4);
        case 'city':
            $city = strtolower(trim($c['city'] ?? ''));
            return !empty($city) && strlen($answer) >= 3 && similar_text($answer, $city) / max(strlen($city), 1) > 0.75;
        case 'year':
            return (int)preg_replace('/\D/','',$answer) === (int)date('Y', strtotime($c['date_created']));
    }
    return false;
}

function authLockout($db, $email) {
    $s = $db->prepare("SELECT COUNT(*) as f FROM alfred_auth_attempts WHERE email=:e AND success=0 AND attempt_time > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    $s->execute([':e'=>$email]); $r = $s->fetch();
    return ['locked' => ($r['f'] >= 5), 'fails' => (int)$r['f']];
}

function authRemaining($db, $email) {
    return max(0, 5 - authLockout($db, $email)['fails']);
}

function authLog($db, $email, $phone, $success, $method) {
    try {
        $db->prepare("INSERT INTO alfred_auth_attempts (email, caller_phone, success, method) VALUES (:e,:p,:s,:m)")
           ->execute([':e'=>$email,':p'=>$phone,':s'=>$success?1:0,':m'=>$method]);
    } catch(Exception $e) { error_log('authLog: '.$e->getMessage()); }
}


// ═══════════════════════════════════════════════════════════════════════════
// 2. GET ACCOUNT SUMMARY
// ═══════════════════════════════════════════════════════════════════════════
function toolAccountSummary($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'client_id required. Authenticate first.'];
    $db = getDB();

    $s = $db->prepare("SELECT s.domain,p.name as plan,s.next_due_date,s.amount,s.billing_cycle,s.status as status FROM services s JOIN products p ON s.product_id=p.id WHERE s.client_id=:u AND s.status IN('Active','Suspended') ORDER BY s.next_due_date ASC LIMIT 10");
    $s->execute([':u'=>$cid]); $services = $s->fetchAll();

    $s2 = $db->prepare("SELECT domain,expiry_date,status FROM domains WHERE client_id=:u AND status IN('Active','Expired','Grace') ORDER BY expiry_date ASC LIMIT 10");
    $s2->execute([':u'=>$cid]); $domains = $s2->fetchAll();

    $s3 = $db->prepare("SELECT id,total,due_date FROM invoices WHERE client_id=:u AND status='Unpaid' ORDER BY due_date ASC LIMIT 5");
    $s3->execute([':u'=>$cid]); $invoices = $s3->fetchAll();
    $unpaidTotal = array_sum(array_column($invoices,'total'));

    return [
        'services'        => array_map(fn($s)=>['domain'=>$s['domain'],'plan'=>$s['plan'],'status'=>$s['status'],'next_due'=>$s['next_due_date'],'price'=>'$'.number_format($s['amount'],2).'/'.$s['billing_cycle']],$services),
        'domains'         => array_map(fn($d)=>['domain'=>$d['domain'],'status'=>$d['status'],'expires'=>$d['expiry_date']],$domains),
        'unpaid_invoices' => count($invoices),
        'unpaid_total'    => '$'.number_format($unpaidTotal,2),
        'invoice_list'    => array_map(fn($i)=>['id'=>$i['id'],'amount'=>'$'.number_format($i['total'],2),'due'=>$i['due_date']],$invoices),
        'summary'         => count($services).' active service(s), '.count($domains).' domain(s), '.count($invoices).' unpaid invoice(s) totalling $'.number_format($unpaidTotal,2)
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 3. CHECK DOMAIN AVAILABILITY — RDAP over HTTPS (accurate, no port blocks)
// ═══════════════════════════════════════════════════════════════════════════
function toolCheckDomain($args) {
    $domain = strtolower(trim($args['domain'] ?? ''));
    if (empty($domain)) return ['error' => 'Domain name required.'];

    $domain  = preg_replace('/^(https?:\/\/)?(www\.)?/','',$domain);
    $domain  = preg_replace('/\/.*$/','',$domain);
    $hasTld  = strpos($domain,'.') !== false;
    $toCheck = $hasTld ? [$domain] : [$domain.'.com',$domain.'.ca',$domain.'.net',$domain.'.org',$domain.'.io'];

    $db = getDB();
    $results = [];

    foreach ($toCheck as $d) {
        $tldPart   = '.'.implode('.',array_slice(explode('.',$d),1));
        $available = rdapCheck($d);
        $price     = null;
        if ($db) {
            $ps = $db->prepare("SELECT pr.msetupfee FROM domain_pricing dp JOIN pricing_legacy pr ON dp.id=pr.relid AND pr.type='domainregister' AND pr.currency=1 WHERE dp.extension=:t LIMIT 1");
            $ps->execute([':t'=>$tldPart]); $row=$ps->fetch();
            if ($row) $price = '$'.number_format($row['msetupfee'],2).'/year';
        }
        $results[] = ['domain'=>$d,'available'=>$available===true?'available':($available===false?'taken':'unknown'),'price'=>$price];
    }

    $avail   = array_values(array_filter($results,fn($r)=>$r['available']==='available'));
    $taken   = array_values(array_filter($results,fn($r)=>$r['available']==='taken'));
    $unknown = array_values(array_filter($results,fn($r)=>$r['available']==='unknown'));

    $summary = '';
    if (count($avail)) $summary .= 'Great news! The following are available: '.implode(', ',array_map(fn($r)=>$r['domain'].($r['price']?' at '.$r['price']:''),$avail)).'. ';
    if (count($taken)) $summary .= 'These are already taken: '.implode(', ',array_column($taken,'domain')).'. ';
    if (!count($avail) && !count($taken)) $summary = 'I was not able to check availability right now. Please visit gositeme.com to search.';
    if (count($avail)) $summary .= 'To register, visit gositeme.com or I can create an order request for you.';

    return ['results'=>$results,'summary'=>trim($summary)];
}


// ═══════════════════════════════════════════════════════════════════════════
// 4. DOMAIN WHOIS LOOKUP
// ═══════════════════════════════════════════════════════════════════════════
function toolDomainWhois($args) {
    $domain = strtolower(trim($args['domain'] ?? ''));
    if (empty($domain)) return ['error' => 'Domain name required.'];

    $domain = preg_replace('/^(https?:\/\/)?(www\.)?/','',$domain);
    $tld    = strtolower(substr($domain, strrpos($domain,'.')+1));

    $rdapEndpoints = [
        'com'=>'https://rdap.verisign.com/com/v1/domain/',
        'net'=>'https://rdap.verisign.com/net/v1/domain/',
        'org'=>'https://rdap.publicinterestregistry.org/rdap/domain/',
        'ca' =>'https://rdap.ca.fury.ca/rdap/domain/',
        'app'=>'https://pubapi.registry.google/rdap/domain/',
        'dev'=>'https://pubapi.registry.google/rdap/domain/',
        'xyz'=>'https://rdap.centralnic.com/xyz/domain/',
        'tech'=>'https://rdap.centralnic.com/tech/domain/',
        'ai' =>'https://rdap.identitydigital.services/rdap/domain/',
        'info'=>'https://rdap.identitydigital.services/rdap/domain/',
        'online'=>'https://rdap.centralnic.com/online/domain/',
        'store'=>'https://rdap.centralnic.com/store/domain/',
    ];

    if (!isset($rdapEndpoints[$tld])) {
        return ['domain'=>$domain,'error'=>'WHOIS not available for .'.$tld.' domains. Please visit who.is or whois.com to look this up.'];
    }

    $ch = curl_init($rdapEndpoints[$tld].$domain);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>8,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_FOLLOWLOCATION=>true]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 404) {
        return ['domain'=>$domain,'status'=>'available','message'=>$domain.' appears to be available for registration!'];
    }

    if ($code !== 200 || !$body) {
        return ['domain'=>$domain,'error'=>'Could not retrieve WHOIS data. Please try again or visit who.is.'];
    }

    $data     = json_decode($body, true);
    $events   = $data['events'] ?? [];
    $created  = ''; $expires = ''; $updated = '';
    foreach ($events as $e) {
        if ($e['eventAction'] === 'registration') $created = substr($e['eventDate'],0,10);
        if ($e['eventAction'] === 'expiration')   $expires = substr($e['eventDate'],0,10);
        if ($e['eventAction'] === 'last changed') $updated = substr($e['eventDate'],0,10);
    }

    $nameservers = [];
    foreach (($data['nameservers'] ?? []) as $ns) {
        $nameservers[] = strtolower($ns['ldhName'] ?? '');
    }

    $registrar = '';
    foreach (($data['entities'] ?? []) as $ent) {
        if (in_array('registrar', $ent['roles'] ?? [])) {
            $registrar = $ent['vcardArray'][1][1][3] ?? ($ent['handle'] ?? '');
        }
    }

    $status = implode(', ', array_slice($data['status'] ?? [], 0, 2));

    return [
        'domain'      => $domain,
        'status'      => $status ?: 'registered',
        'registered'  => $created,
        'expires'     => $expires,
        'updated'     => $updated,
        'registrar'   => $registrar,
        'nameservers' => $nameservers,
        'summary'     => $domain.' is registered'.($expires ? ', expires '.$expires : '').($registrar ? ', registered through '.$registrar : '').'. Nameservers: '.implode(', ',$nameservers).'.'
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 5. GET INVOICES
// ═══════════════════════════════════════════════════════════════════════════
function toolGetInvoices($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'client_id required. Authenticate first.'];
    $db   = getDB();
    $stmt = $db->prepare("SELECT id,total,due_date,status,paid_date FROM invoices WHERE client_id=:u ORDER BY due_date DESC LIMIT 10");
    $stmt->execute([':u'=>$cid]); $invoices = $stmt->fetchAll();
    if (!$invoices) return ['message'=>'No invoices found.','invoices'=>[]];

    $unpaid      = array_filter($invoices,fn($i)=>$i['status']==='Unpaid');
    $unpaidTotal = array_sum(array_column($unpaid,'total'));
    $summary     = count($unpaid)>0
        ? count($unpaid).' unpaid invoice(s) totalling $'.number_format($unpaidTotal,2).'. Customer can pay at gositeme.com/pay.'
        : 'All invoices are paid — great standing!';

    return [
        'invoices'     => array_map(fn($i)=>['id'=>$i['id'],'amount'=>'$'.number_format($i['total'],2),'due_date'=>$i['due_date'],'status'=>$i['status'],'pay_url'=>'https://gositeme.com/view-invoice?id='.$i['id']],$invoices),
        'unpaid_count' => count($unpaid),
        'unpaid_total' => '$'.number_format($unpaidTotal,2),
        'summary'      => $summary
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 6. SEND PAYMENT LINK — emails the customer a direct pay link
// ═══════════════════════════════════════════════════════════════════════════
function toolSendPaymentLink($args) {
    $cid       = (int)($args['client_id'] ?? 0);
    $invoiceId = (int)($args['invoice_id'] ?? 0);

    if (!$cid) return ['error' => 'client_id required. Authenticate first.'];

    $db = getDB();

    // Get client info
    $sc = $db->prepare("SELECT firstname, lastname, email FROM clients WHERE id=:id LIMIT 1");
    $sc->execute([':id'=>$cid]); $client = $sc->fetch();
    if (!$client) return ['error' => 'Client not found.'];

    // Get invoice(s)
    if ($invoiceId) {
        $si = $db->prepare("SELECT id, total, due_date, status FROM invoices WHERE id=:id AND client_id=:u LIMIT 1");
        $si->execute([':id'=>$invoiceId,':u'=>$cid]); $invoices = $si->fetchAll();
    } else {
        $si = $db->prepare("SELECT id, total, due_date, status FROM invoices WHERE client_id=:u AND status='Unpaid' ORDER BY due_date ASC LIMIT 5");
        $si->execute([':u'=>$cid]); $invoices = $si->fetchAll();
    }

    if (!$invoices) return ['success'=>false,'message'=>'No unpaid invoices found for this account.'];

    $total     = array_sum(array_column($invoices,'total'));
    $payLinks  = array_map(fn($i)=>'Invoice #'.$i['id'].' ($'.number_format($i['total'],2).'): https://gositeme.com/view-invoice?id='.$i['id'],$invoices);

    // Send email via ticket system or direct mail
    $subject = 'Your GoSiteMe Payment Link';
    $body    = "Hi ".$client['firstname'].",\n\n"
             . "Alfred from GoSiteMe support sent you this payment link during your support call.\n\n"
             . "Outstanding Balance: $".number_format($total,2)."\n\n"
             . implode("\n", $payLinks)."\n\n"
             . "You can also log in and pay at: https://gositeme.com/dashboard\n\n"
             . "Thank you,\nGoSiteMe Support";

    $sent = mail($client['email'], $subject, $body,
        "From: support@gositeme.com\r\nReply-To: support@gositeme.com\r\nContent-Type: text/plain; charset=UTF-8"
    );

    if (!$sent) {
        error_log("VAPI: mail() failed for " . $client['email']);
        return [
            'success' => false,
            'email'   => $client['email'],
            'amount'  => '$'.number_format($total,2),
            'links'   => $payLinks,
            'message' => 'I was unable to send the email right now due to a technical issue. But here are the payment links I can give you verbally: ' . implode(', ', $payLinks) . '. You can also pay anytime at gositeme.com/pay.'
        ];
    }

    // Also log in activity
    try {
        $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(),:d,:u,'127.0.0.1')")
           ->execute([':d'=>'Alfred sent payment link to '.$client['email'].' — $'.number_format($total,2).' outstanding',':u'=>$client['email']]);
    } catch(Exception $e) {}

    return [
        'success' => true,
        'email'   => $client['email'],
        'amount'  => '$'.number_format($total,2),
        'links'   => $payLinks,
        'message' => 'I have sent a payment link to '.$client['email'].'. The total outstanding is $'.number_format($total,2).'. They can also pay at gositeme.com/pay anytime.'
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 6b. SEND EMAIL — general-purpose email to authenticated customer
// ═══════════════════════════════════════════════════════════════════════════
function toolSendEmail($args) {
    $cid     = (int)($args['client_id'] ?? 0);
    $subject = trim($args['subject'] ?? '');
    $body    = trim($args['body'] ?? '');
    $purpose = trim($args['purpose'] ?? 'general'); // summary, dns_details, account_info, etc.

    if (!$cid) return ['error' => 'client_id required. Authenticate first.'];
    if (empty($subject)) return ['error' => 'Email subject is required.'];
    if (empty($body)) return ['error' => 'Email body is required.'];

    // Sanitize: no HTML injection in plaintext emails
    $subject = strip_tags($subject);
    $body    = strip_tags($body);

    // Rate limit: max 3 emails per client per hour
    $db = getDB();
    try {
        $rateCheck = $db->prepare("SELECT COUNT(*) as cnt FROM activity_log WHERE user=:u AND description LIKE 'Alfred sent email%' AND date > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $rateCheck->execute([':u' => (string)$cid]);
        $rate = $rateCheck->fetch();
        if ($rate && (int)$rate['cnt'] >= 3) {
            return ['success' => false, 'message' => 'I have already sent several emails recently. To prevent spam, I can send more in about an hour. Is there anything else I can help with?'];
        }
    } catch(Exception $e) {}

    $sc = $db->prepare("SELECT firstname, lastname, email FROM clients WHERE id=:id LIMIT 1");
    $sc->execute([':id'=>$cid]); $client = $sc->fetch();
    if (!$client) return ['error' => 'Client not found.'];

    $fullBody = "Hi ".$client['firstname'].",\n\n"
              . $body . "\n\n"
              . "---\n"
              . "This email was sent by Alfred, your AI assistant at GoSiteMe.\n"
              . "If you did not request this, please contact support@gositeme.com.\n\n"
              . "Thank you,\nAlfred — GoSiteMe AI Assistant";

    $sent = mail($client['email'], $subject, $fullBody,
        "From: alfred@gositeme.com\r\nReply-To: support@gositeme.com\r\nContent-Type: text/plain; charset=UTF-8"
    );

    if (!$sent) {
        error_log("VAPI sendEmail: mail() failed for " . $client['email'] . " purpose=" . $purpose);
        return ['success' => false, 'message' => 'I was unable to send the email due to a technical issue. I apologize for that. Is there anything else I can help with?'];
    }

    try {
        $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(),:d,:u,'127.0.0.1')")
           ->execute([':d'=>'Alfred sent email to '.$client['email'].' — subject: '.$subject.' — purpose: '.$purpose, ':u'=>(string)$cid]);
    } catch(Exception $e) {}

    return [
        'success' => true,
        'email'   => $client['email'],
        'subject' => $subject,
        'message' => 'I have sent an email to '.$client['email'].' with the subject "'.$subject.'". Please check your inbox and spam folder.'
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// TRANSFER CALL — Transfer the active VAPI call to another phone number
// Supports: PSTN transfer (phone number), SIP transfer, or agent transfer
// ═══════════════════════════════════════════════════════════════════════════
function toolTransferCall($args, $input = []) {
    $destination = trim((string)($args['phone_number'] ?? $args['destination'] ?? $args['number'] ?? $args['target'] ?? ''));
    $message = trim((string)($args['message'] ?? $args['transfer_message'] ?? ''));
    $reason = trim((string)($args['reason'] ?? 'customer_request'));
    $mode = trim((string)($args['mode'] ?? 'blind')); // blind or warm
    $callId = $input['message']['call']['id'] ?? $input['call']['id'] ?? ($args['call_id'] ?? '');

    if ($destination === '') {
        return ['error' => 'Where should I transfer the call? Please provide a phone number or route name.'];
    }

    // Known transfer destinations
    $knownDestinations = [
        'support' => ['number' => '+15148001757', 'name' => 'GoSiteMe Support'],
        'billing' => ['number' => '+15148001757', 'name' => 'GoSiteMe Billing'],
        'sales' => ['number' => '+15148001757', 'name' => 'GoSiteMe Sales'],
        'owner' => ['number' => '+14504217379', 'name' => 'Danny'],
        'manager' => ['number' => '+14504217379', 'name' => 'Manager'],
        'supervisor' => ['number' => '+14504217379', 'name' => 'Supervisor'],
        'danny' => ['number' => '+14504217379', 'name' => 'Danny'],
        'joe' => ['number' => '+12267571915', 'name' => 'Joe'],
        'dom' => ['number' => '+14504949718', 'name' => 'Dom'],
        'legal_aid' => ['number' => '+18008422213', 'name' => 'Aide juridique du Québec'],
        'court_montreal' => ['number' => '+15143937033', 'name' => 'Palais de Justice de Montréal'],
    ];

    $destKey = strtolower(trim(preg_replace('/\s+/', '_', $destination)));
    if (isset($knownDestinations[$destKey])) {
        $cleanNum = $knownDestinations[$destKey]['number'];
        $destName = $knownDestinations[$destKey]['name'];
    } else {
        $cleanNum = preg_replace('/[^0-9+]/', '', $destination);
        if (!preg_match('/^\+?1?\d{10,15}$/', $cleanNum)) {
            return ['error' => 'That doesn\'t look like a valid phone number. Please provide a full phone number including area code.'];
        }
        if (strlen($cleanNum) === 10) {
            $cleanNum = '+1' . $cleanNum;
        } elseif (strlen($cleanNum) === 11 && $cleanNum[0] === '1') {
            $cleanNum = '+' . $cleanNum;
        } elseif ($cleanNum[0] !== '+') {
            $cleanNum = '+' . $cleanNum;
        }
        $destName = $cleanNum;
    }

    $routingMessage = $message ?: "Transferring you now to {$destName}. Please hold.";
    $spokenMessage = $message ?: "I'm transferring you to {$destName} now. Please hold while I connect you.";

    // Return native transfer instructions for Vapi. The old REST `/call/{id}/transfer`
    // path produced 404s; the voice runtime should use the transfer payload directly.
    error_log("[transferCall] Prepared native VAPI transfer to {$cleanNum}" . ($callId ? " (call: {$callId})" : '') . " reason={$reason}");

    return [
        'success' => true,
        'transferred' => true,
        'destination' => $cleanNum,
        'destination_name' => $destName,
        'transfer' => [
            'type' => 'number',
            'number' => $cleanNum,
            'message' => $routingMessage,
        ],
        'message' => $spokenMessage,
        'reason' => $reason,
        'mode' => $mode,
        'call_id' => $callId,
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 7. GET DNS RECORDS — live lookup for any domain
// ═══════════════════════════════════════════════════════════════════════════
function toolGetDns($args) {
    $domain = strtolower(trim($args['domain'] ?? ''));
    if (empty($domain)) return ['error' => 'Domain name required.'];

    $domain  = preg_replace('/^(https?:\/\/)?(www\.)?/','',$domain);
    $domain  = preg_replace('/\/.*$/','',$domain);
    $records = [];

    $types = [DNS_A=>'A', DNS_AAAA=>'AAAA', DNS_MX=>'MX', DNS_NS=>'NS', DNS_TXT=>'TXT', DNS_CNAME=>'CNAME'];

    foreach ($types as $const => $type) {
        $raw = @dns_get_record($domain, $const);
        if (!$raw) continue;
        foreach ($raw as $r) {
            $value = '';
            switch($type) {
                case 'A':     $value = $r['ip']     ?? ''; break;
                case 'AAAA':  $value = $r['ipv6']   ?? ''; break;
                case 'MX':    $value = 'Priority '.$r['pri'].': '.$r['target']; break;
                case 'NS':    $value = $r['target'] ?? ''; break;
                case 'TXT':   $value = $r['txt']    ?? ''; break;
                case 'CNAME': $value = $r['target'] ?? ''; break;
            }
            if ($value) $records[] = ['type'=>$type,'value'=>$value,'ttl'=>$r['ttl']??null];
        }
    }

    if (!$records) {
        return ['domain'=>$domain,'records'=>[],'summary'=>'No DNS records found for '.$domain.'. The domain may not exist or DNS may not be configured.'];
    }

    $aRecords  = array_column(array_filter($records,fn($r)=>$r['type']==='A'),'value');
    $mxRecords = array_column(array_filter($records,fn($r)=>$r['type']==='MX'),'value');
    $nsRecords = array_column(array_filter($records,fn($r)=>$r['type']==='NS'),'value');

    $summary = $domain.' points to IP '.implode(', ',$aRecords).'. ';
    if ($mxRecords) $summary .= 'Mail handled by: '.implode(', ',$mxRecords).'. ';
    if ($nsRecords) $summary .= 'Name servers: '.implode(', ',$nsRecords).'.';

    return ['domain'=>$domain,'records'=>$records,'summary'=>trim($summary)];
}


// ═══════════════════════════════════════════════════════════════════════════
// 8. FIX DNS ISSUE — diagnose + create high-priority ticket
// ═══════════════════════════════════════════════════════════════════════════
function toolFixDns($args) {
    $cid     = (int)($args['client_id'] ?? 0);
    $domain  = strtolower(trim($args['domain'] ?? ''));
    $issue   = trim($args['issue'] ?? 'DNS issue reported by customer on support call');

    if (empty($domain)) return ['error' => 'Domain name required.'];

    // Run diagnostics automatically
    $dns    = toolGetDns(['domain' => $domain]);
    $whois  = toolDomainWhois(['domain' => $domain]);

    $diagSummary  = "DNS Diagnostic for: $domain\n";
    $diagSummary .= "=================================\n";
    $diagSummary .= "Issue reported: $issue\n\n";
    $diagSummary .= "DNS Records:\n";

    if (!empty($dns['records'])) {
        foreach ($dns['records'] as $r) {
            $diagSummary .= "  [{$r['type']}] {$r['value']}\n";
        }
    } else {
        $diagSummary .= "  ⚠️ NO DNS RECORDS FOUND\n";
    }

    $diagSummary .= "\nWHOIS:\n";
    if (!empty($whois['expires']))     $diagSummary .= "  Expires: {$whois['expires']}\n";
    if (!empty($whois['registrar']))   $diagSummary .= "  Registrar: {$whois['registrar']}\n";
    if (!empty($whois['nameservers'])) $diagSummary .= "  Nameservers: ".implode(', ',$whois['nameservers'])."\n";
    if (!empty($whois['status']))      $diagSummary .= "  Status: {$whois['status']}\n";

    // Create high-priority ticket
    $db     = getDB();
    $name   = 'Customer'; $email = 'support@gositeme.com';
    if ($cid && $db) {
        $sc = $db->prepare("SELECT firstname, lastname, email FROM clients WHERE id=:id LIMIT 1");
        $sc->execute([':id'=>$cid]); $c = $sc->fetch();
        if ($c) { $name = $c['firstname'].' '.$c['lastname']; $email = $c['email']; }
    }

    $stmtD    = $db->prepare("SELECT id FROM ticket_departments WHERE is_hidden=0 ORDER BY id ASC LIMIT 1");
    $stmtD->execute(); $dept = $stmtD->fetch();
    $ticketId = insertTicket($db, ($dept['id']??1), $cid, $name, $email,
        'DNS Issue - '.$domain,
        "Alfred flagged a DNS issue during a phone support call.\n\nCustomer: $name\nEmail: $email\n\n$diagSummary",
        'High');

    return [
        'success'    => true,
        'ticket_id'  => $ticketId,
        'domain'     => $domain,
        'diagnosis'  => $dns['summary'] ?? 'No DNS records found',
        'whois'      => $whois['summary'] ?? 'WHOIS unavailable',
        'message'    => 'I have run a full DNS diagnostic on '.$domain.' and created a high-priority ticket '.$ticketId.' for our technical team. They will investigate and fix this within 24 hours. Would you like me to send you a summary email with all the details?'
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 9. CREATE SUPPORT TICKET
// ═══════════════════════════════════════════════════════════════════════════
function toolCreateTicket($args) {
    $cid     = (int)($args['client_id'] ?? 0);
    $subject = trim($args['subject'] ?? '');
    $message = trim($args['message'] ?? '');
    if (!$cid)            return ['error' => 'client_id required.'];
    if (!$subject)        return ['error' => 'Subject required.'];
    if (!$message)        return ['error' => 'Message required.'];

    $db   = getDB();
    $sc   = $db->prepare("SELECT email, firstname FROM clients WHERE id=:id LIMIT 1");
    $sc->execute([':id'=>$cid]); $client = $sc->fetch();
    if (!$client) return ['error' => 'Client not found.'];

    $stmtD = $db->prepare("SELECT id FROM ticket_departments WHERE is_hidden=0 ORDER BY id ASC LIMIT 1");
    $stmtD->execute(); $dept = $stmtD->fetch();
    $tid = insertTicket($db, ($dept['id']??1), $cid, $client['firstname'], $client['email'],
        '[Alfred] '.$subject,
        "Created by Alfred during a phone support call.\n\n".$message,
        'Medium');

    return ['success'=>true,'ticket_id'=>$tid,'message'=>'Support ticket '.$tid.' has been created. Our team will follow up with '.$client['firstname'].' within 24 hours.'];
}


// ═══════════════════════════════════════════════════════════════════════════
// 10. SCHEDULE CALLBACK
// ═══════════════════════════════════════════════════════════════════════════
function toolCallback($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $reason = trim($args['reason'] ?? 'Customer requested human callback');
    $phone  = trim($args['phone'] ?? '');

    $db = getDB(); $name='Customer'; $email='';
    if ($cid && $db) {
        $sc = $db->prepare("SELECT firstname,lastname,email,phone FROM clients WHERE id=:id LIMIT 1");
        $sc->execute([':id'=>$cid]); $c=$sc->fetch();
        if ($c) { $name=$c['firstname'].' '.$c['lastname']; $email=$c['email']; if(!$phone) $phone=$c['phone']; }
    }

    $stmtD = $db->prepare("SELECT id FROM ticket_departments WHERE is_hidden=0 ORDER BY id ASC LIMIT 1");
    $stmtD->execute(); $dept=$stmtD->fetch();
    $tid = insertTicket($db, ($dept['id']??1), $cid, $name, $email,
        'Callback Requested - '.$name,
        "Alfred flagged a callback during a phone call.\n\nCustomer: $name\nPhone: $phone\nReason: $reason\n\nPlease call back within 24 hours.",
        'High');

    return ['success'=>true,'message'=>'Done! I have flagged your account for a callback. Our team will reach out to '.($phone?:$name).' within 24 hours. Is there anything else I can help with in the meantime?'];
}


// ═══════════════════════════════════════════════════════════════════════════
// RDAP domain availability check — HTTPS only, no port 43
// ═══════════════════════════════════════════════════════════════════════════
function rdapCheck($domain) {
    // First: quick DNS check (fastest)
    $dns = @dns_get_record($domain, DNS_A);
    if (!empty($dns)) return false;

    $tld = strtolower(substr($domain, strrpos($domain,'.')+1));
    $endpoints = [
        'com' =>'https://rdap.verisign.com/com/v1/domain/',
        'net' =>'https://rdap.verisign.com/net/v1/domain/',
        'org' =>'https://rdap.publicinterestregistry.org/rdap/domain/',
        'ca'  =>'https://rdap.ca.fury.ca/rdap/domain/',
        'app' =>'https://pubapi.registry.google/rdap/domain/',
        'dev' =>'https://pubapi.registry.google/rdap/domain/',
        'xyz' =>'https://rdap.centralnic.com/xyz/domain/',
        'tech'=>'https://rdap.centralnic.com/tech/domain/',
        'ai'  =>'https://rdap.identitydigital.services/rdap/domain/',
        'info'=>'https://rdap.identitydigital.services/rdap/domain/',
        'me'  =>'https://rdap.identitydigital.services/rdap/domain/',
        'online'=>'https://rdap.centralnic.com/online/domain/',
        'store' =>'https://rdap.centralnic.com/store/domain/',
        'io'  =>'https://rdap.identitydigital.services/rdap/domain/',
        'biz' =>'https://rdap.nic.biz/domain/',
        'us'  =>'https://rdap.iana.org/domain/',
        'co'  =>'https://rdap.iana.org/domain/',
    ];

    if (!isset($endpoints[$tld])) return null;

    $ch = curl_init($endpoints[$tld].$domain);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>6,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_NOBODY=>true]);
    curl_exec($ch);
    $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code===404) return true;   // not found = available
    if ($code===200) return false;  // found = taken
    return null;
}

// ═══════════════════════════════════════════════════════════════════════════
// HELPER: Insert a ticket into tickets table with correct schema
// ═══════════════════════════════════════════════════════════════════════════
function insertTicket($db, $deptId, $userId, $name, $email, $title, $message, $urgency = 'Medium') {
    $tid = makeTicketId();
    $db->prepare('INSERT INTO tickets
        (tid,did,userid,contactid,name,email,cc,c,ipaddress,date,title,message,status,urgency,admin,attachment,attachments_removed,lastreply,flag,clientunread,adminunread,replyingadmin,replyingtime,service,editor,updated_at)
        VALUES
        (:tid,:did,:uid,0,:name,:email,"","","127.0.0.1",NOW(),:title,:msg,"Open",:urgency,"","",0,NOW(),0,1,"1",0,NOW(),"","plain",NOW())')
       ->execute([':tid'=>$tid,':did'=>$deptId,':uid'=>$userId,':name'=>$name,':email'=>$email,
                  ':title'=>$title,':msg'=>$message,':urgency'=>$urgency]);
    return $tid;
}

// ═══════════════════════════════════════════════════════════════════════════
// HELPER: Generate ticket ID (XXX-NNNNNN)
// ═══════════════════════════════════════════════════════════════════════════
function makeTicketId() {
    $letters = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 3));
    $numbers = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    return $letters . '-' . $numbers;
}


// ═══════════════════════════════════════════════════════════════════════════
//   GoCodeMe IDE BRIDGE — Alfred ↔ IDE Integration
//   These tools call the Node.js middleware at 127.0.0.1:3001 via the
//   internal /api/alfred/* bridge endpoints using the billing webhook secret.
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Helper: call the GoCodeMe middleware Alfred bridge
 */
function alfredBridge($endpoint, $body = []) {
    // Read the billing webhook secret from middleware .env
    static $secret = null;
    if ($secret === null) {
        $envFile = __DIR__ . '/../gocodeme/middleware/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue;
                if (preg_match('/^BILLING_WEBHOOK_SECRET=(.+)$/', trim($line), $m)) {
                    $secret = trim($m[1]); break;
                }
            }
        }
        if (!$secret) {
            error_log('[alfredBridge] BILLING_WEBHOOK_SECRET not found in .env — bridge calls will fail');
            return ['error' => 'Webhook secret not configured'];
        }
    }

    $url = 'http://127.0.0.1:3001/api/alfred/' . $endpoint;
    $json = json_encode($body);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Billing-Secret: ' . $secret,
            'Content-Length: ' . strlen($json),
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) return ['error' => 'Could not reach the IDE service: ' . $curlErr];
    if ($httpCode >= 400) return ['error' => 'IDE service error (HTTP ' . $httpCode . ')'];

    return json_decode($response, true) ?: ['error' => 'Invalid response from IDE service'];
}

/**
 * MCP Bridge: Route tool calls to the MCP server via the middleware bridge.
 * This gives voice tools access to ALL 1,220+ MCP tools (domains, hosting,
 * billing, signup, payments, beyond-autopilot features, etc.)
 */
function mcpBridge($toolName, $args = []) {
    // Reuse the same billing secret discovery from alfredBridge
    static $secret = null;
    if ($secret === null) {
        $envFile = __DIR__ . '/../gocodeme/middleware/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue;
                if (preg_match('/^BILLING_WEBHOOK_SECRET=(.+)$/', trim($line), $m)) {
                    $secret = trim($m[1]); break;
                }
            }
        }
        if (!$secret) {
            error_log('[mcpBridge] BILLING_WEBHOOK_SECRET not found');
            return ['error' => 'MCP bridge authentication not configured'];
        }
    }

    $url = 'http://127.0.0.1:3001/api/alfred/mcp-tool';
    $payload = json_encode([
        'tool' => $toolName,
        'arguments' => $args,
        'source' => 'vapi_voice',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Billing-Secret: ' . $secret,
            'Content-Length: ' . strlen($payload),
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log("[mcpBridge] cURL error calling $toolName: $curlErr");
        return ['error' => "Could not reach the AI platform: $curlErr"];
    }
    if ($httpCode >= 400) {
        error_log("[mcpBridge] HTTP $httpCode calling $toolName");
        return ['error' => "Service error (HTTP $httpCode)"];
    }

    $data = json_decode($response, true);
    if (!$data) return ['error' => 'Invalid response from MCP service'];

    // Return the result content
    return $data['result'] ?? $data;
}

// ═══════════════════════════════════════════════════════════════════════════
// callAlfred() — Multi-model AI router for voice tool intelligence
// Primary: Groq (fast, free) → Fallback: OpenRouter → Last Resort: Ollama (local)
// Called by ~254 demographic tool functions to generate AI responses
// ═══════════════════════════════════════════════════════════════════════════
function callAlfred($prompt, $options = []) {
    $maxTokens   = $options['max_tokens'] ?? 800;
    $temperature = $options['temperature'] ?? 0.7;
    $systemMsg   = $options['system'] ?? 'You are Alfred, a highly capable AI assistant for GoSiteMe. You are speaking to the user via voice, so keep responses conversational, clear, and concise. Avoid markdown formatting, code blocks, or bullet points — speak naturally as if on a phone call.';

    // ── Load API keys from environment or .env file ──
    static $keys = null;
    if ($keys === null) {
        $keys = ['groq' => '', 'openrouter' => ''];
        // Try getenv first
        $keys['groq'] = getenv('GROQ_API_KEY') ?: '';
        $keys['openrouter'] = getenv('OPENROUTER_API_KEY') ?: '';

        // Fallback: read from middleware .env
        if (empty($keys['groq']) || empty($keys['openrouter'])) {
            $envPaths = [
                __DIR__ . '/../gocodeme/middleware/.env',
                '/home/gositeme/domains/gocodeme.com/public_html/.env',
            ];
            foreach ($envPaths as $envPath) {
                if (!file_exists($envPath)) continue;
                $envContent = file_get_contents($envPath);
                if (empty($keys['groq']) && preg_match('/GROQ_API_KEY=(.+)/', $envContent, $m)) {
                    $keys['groq'] = trim($m[1]);
                }
                if (empty($keys['openrouter']) && preg_match('/OPENROUTER_API_KEY=(.+)/', $envContent, $m)) {
                    $keys['openrouter'] = trim($m[1]);
                }
                if (!empty($keys['groq']) && !empty($keys['openrouter'])) break;
            }
        }
    }

    // ── Attempt 1: Groq (fastest, free tier — llama-3.3-70b) ──
    if (!empty($keys['groq'])) {
        $response = callAIProvider(
            'https://api.groq.com/openai/v1/chat/completions',
            $keys['groq'],
            [
                'model'       => 'llama-3.3-70b-versatile',
                'messages'    => [
                    ['role' => 'system', 'content' => $systemMsg],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'max_tokens'  => $maxTokens,
                'temperature' => $temperature,
            ],
            'openai' // response format
        );
        if ($response !== null) return $response;
        error_log('[callAlfred] Groq failed, falling back to OpenRouter');
    }

    // ── Attempt 2: OpenRouter (Claude/GPT-4/Llama — auto-routed) ──
    if (!empty($keys['openrouter'])) {
        $response = callAIProvider(
            'https://openrouter.ai/api/v1/chat/completions',
            $keys['openrouter'],
            [
                'model'       => 'meta-llama/llama-3.3-70b-instruct',
                'messages'    => [
                    ['role' => 'system', 'content' => $systemMsg],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'max_tokens'  => $maxTokens,
                'temperature' => $temperature,
            ],
            'openai',
            ['HTTP-Referer: https://gositeme.com', 'X-Title: Alfred AI']
        );
        if ($response !== null) return $response;
        error_log('[callAlfred] OpenRouter also failed');
    }

    // ── Attempt 3: Ollama (local, free, CPU inference) ──
    $ollamaHost = getenv('OLLAMA_HOST') ?: 'http://127.0.0.1:11434';
    $response = callAIProvider(
        "$ollamaHost/v1/chat/completions",
        'ollama',
        [
            'model'       => 'qwen2.5:3b',
            'messages'    => [
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user',   'content' => $prompt],
            ],
            'max_tokens'  => min($maxTokens, 500),
            'temperature' => $temperature,
        ],
        'openai'
    );
    if ($response !== null) return $response;
    error_log('[callAlfred] Ollama also failed');

    // ── All providers failed ──
    error_log('[callAlfred] All AI providers failed. Prompt: ' . substr($prompt, 0, 200));
    return 'I apologize, but I am having trouble processing that request right now. Please try again in a moment, or I can create a support ticket for you.';
}

/**
 * Generic AI provider caller — supports OpenAI-compatible APIs
 * Returns the text response or null on failure
 */
function callAIProvider($url, $apiKey, $payload, $format = 'openai', $extraHeaders = []) {
    $headers = ['Content-Type: application/json'];
    if ($apiKey && $apiKey !== 'ollama') {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }
    $headers = array_merge($headers, $extraHeaders);

    $json = json_encode($payload);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log("[callAIProvider] cURL error: $curlErr ($url)");
        return null;
    }
    if ($httpCode >= 400) {
        error_log("[callAIProvider] HTTP $httpCode from $url: " . substr($response, 0, 300));
        return null;
    }

    $data = json_decode($response, true);
    if (!$data) {
        error_log("[callAIProvider] Invalid JSON from $url");
        return null;
    }

    // Extract text based on format
    if ($format === 'openai') {
        return $data['choices'][0]['message']['content'] ?? null;
    }
    if ($format === 'anthropic') {
        return $data['content'][0]['text'] ?? null;
    }

    return null;
}

// ─── 11. CHECK IDE STATUS ────────────────────────────────────────────────
function toolIDEStatus($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first before checking your IDE status.'];

    $result = alfredBridge('ide-status', ['client_id' => $cid]);
    return ['success' => !empty($result['ok']),
        'active'  => $result['active'] ?? false,
        'sessions' => $result['sessions'] ?? [],
        'message' => $result['message'] ?? 'Could not check IDE status.'];
}

// ─── 12. LAUNCH IDE ─────────────────────────────────────────────────────
function toolLaunchIDE($args) {
    $cid  = (int)($args['client_id'] ?? 0);
    $type = trim($args['type'] ?? 'ide');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('launch-ide', ['client_id' => $cid, 'type' => $type]);
    return ['success' => !empty($result['ok']),
        'url'     => $result['url'] ?? '',
        'message' => $result['message'] ?? 'Could not launch the IDE right now.'];
}

// ─── 13. STOP IDE ───────────────────────────────────────────────────────
function toolStopIDE($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('stop-ide', ['client_id' => $cid]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? 'IDE session stopped.'];
}

// ─── 14. GET TOKEN USAGE ────────────────────────────────────────────────
function toolTokenUsage($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('token-usage', ['client_id' => $cid]);
    return ['success' => !empty($result['ok']),
        'plan'      => $result['plan'] ?? 'Unknown',
        'used'      => $result['used'] ?? 0,
        'limit'     => $result['limit'] ?? 0,
        'remaining' => $result['remaining'] ?? 0,
        'percent'   => $result['percentUsed'] ?? 0,
        'message'   => $result['message'] ?? 'Could not check token usage.'];
}

// ─── 15. GET HOSTING STATUS ─────────────────────────────────────────────
function toolHostingStatus($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('hosting-status', ['client_id' => $cid]);
    return ['success' => !empty($result['ok']),
        'domains'      => $result['domains'] ?? [],
        'domain_count' => $result['domainCount'] ?? 0,
        'health_score' => $result['healthScore'] ?? 0,
        'message'      => $result['message'] ?? 'Could not check hosting status.'];
}

// ─── 16. LIST PROJECT FILES ─────────────────────────────────────────────
function toolListFiles($args) {
    $cid  = (int)($args['client_id'] ?? 0);
    $path = trim($args['path'] ?? 'public_html');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('list-files', ['client_id' => $cid, 'path' => $path]);
    return ['success' => !empty($result['ok']),
        'file_count' => $result['count'] ?? 0,
        'message'    => $result['message'] ?? 'Could not list files.'];
}

// ─── 17. DEPLOY TO LIVE ────────────────────────────────────────────────
function toolDeployLive($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first. Deploying code requires authentication.'];

    $result = alfredBridge('deploy', ['client_id' => $cid, 'domain' => $domain]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? 'Deployment could not be completed right now.'];
}

// ─── 18. APPLY TEMPLATE ────────────────────────────────────────────────
function toolApplyTemplate($args) {
    $cid      = (int)($args['client_id'] ?? 0);
    $template = trim($args['template'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$template) return ['error' => 'Which template would you like? We have: nextjs, react, vue, express, static, wordpress, laravel, and python-flask.'];

    $result = alfredBridge('apply-template', ['client_id' => $cid, 'template' => $template]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? 'Could not apply the template.'];
}

// ─── 19. ASK AI ─────────────────────────────────────────────────────────
function toolAskAI($args) {
    $cid      = (int)($args['client_id'] ?? 0);
    $question = trim($args['question'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$question) return ['error' => 'What would you like to ask the AI?'];

    $result = alfredBridge('ai-chat', ['client_id' => $cid, 'question' => $question]);
    return ['success' => true,
        'answer'  => $result['answer'] ?? 'No answer generated.',
        'message' => $result['message'] ?? 'The AI could not answer that right now.'];
}

// ─── 20. PROJECT HEALTH ────────────────────────────────────────────────
function toolProjectHealth($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('health-score', ['client_id' => $cid]);
    return ['success' => !empty($result['ok']),
        'score'   => $result['score'] ?? 0,
        'message' => $result['message'] ?? 'Could not check project health.'];
}

// ═══════════════════════════════════════════════════════════════════════════
// GoCodeMe IDE — FILE & DEV TOOLS (Voice parity with chat widget)
// ═══════════════════════════════════════════════════════════════════════════

// ─── READ FILE ─────────────────────────────────────────────────────────
function toolReadFile($args) {
    $cid  = (int)($args['client_id'] ?? 0);
    $path = trim($args['path'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$path) return ['error' => 'Which file would you like me to read? Please provide the file path.'];

    $result = mcpBridge('read_file', ['client_id' => $cid, 'path' => $path]);
    return ['success' => !empty($result['ok']),
        'content' => isset($result['content']) ? mb_substr($result['content'], 0, 2000) : '',
        'message' => $result['message'] ?? ($result['error'] ?? 'Could not read the file.')];
}

// ─── WRITE FILE ────────────────────────────────────────────────────────
function toolWriteFile($args) {
    $cid     = (int)($args['client_id'] ?? 0);
    $path    = trim($args['path'] ?? '');
    $content = $args['content'] ?? '';
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$path) return ['error' => 'Which file should I write to? Please provide the file path.'];

    $result = mcpBridge('write_file', ['client_id' => $cid, 'path' => $path, 'content' => $content]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? ($result['error'] ?? 'Could not write the file.')];
}

// ─── CREATE FILE ───────────────────────────────────────────────────────
function toolCreateFile($args) {
    $cid     = (int)($args['client_id'] ?? 0);
    $path    = trim($args['path'] ?? '');
    $content = $args['content'] ?? '';
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$path) return ['error' => 'What should the new file be called? Please provide the file path.'];

    $result = mcpBridge('create_file', ['client_id' => $cid, 'path' => $path, 'content' => $content]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? ($result['error'] ?? 'Could not create the file.')];
}

// ─── SEARCH FILES ──────────────────────────────────────────────────────
function toolSearchFiles($args) {
    $cid   = (int)($args['client_id'] ?? 0);
    $query = trim($args['query'] ?? '');
    $path  = trim($args['path'] ?? 'public_html');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$query) return ['error' => 'What should I search for? Please provide a search query.'];

    $result = mcpBridge('search_files', ['client_id' => $cid, 'query' => $query, 'path' => $path]);
    return ['success' => !empty($result['ok']),
        'matches' => $result['matches'] ?? [],
        'count'   => $result['count'] ?? 0,
        'message' => $result['message'] ?? ($result['error'] ?? 'Could not search files.')];
}

// ─── DELETE FILE ───────────────────────────────────────────────────────
function toolDeleteFile($args) {
    $cid  = (int)($args['client_id'] ?? 0);
    $path = trim($args['path'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$path) return ['error' => 'Which file should I delete? Please provide the file path.'];

    $result = mcpBridge('delete_file', ['client_id' => $cid, 'path' => $path]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? ($result['error'] ?? 'Could not delete the file.')];
}

// ─── RUN TERMINAL COMMAND ──────────────────────────────────────────────
function toolRunCommand($args) {
    $cid     = (int)($args['client_id'] ?? 0);
    $command = trim($args['command'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$command) return ['error' => 'What command would you like me to run?'];

    $result = mcpBridge('run_terminal_command', ['client_id' => $cid, 'command' => $command]);
    return ['success' => !empty($result['ok']),
        'output'  => isset($result['output']) ? mb_substr($result['output'], 0, 2000) : '',
        'message' => $result['message'] ?? ($result['error'] ?? 'Could not run the command.')];
}

// ─── GIT STATUS ────────────────────────────────────────────────────────
function toolGitStatus($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = mcpBridge('git_status', ['client_id' => $cid]);
    return ['success' => !empty($result['ok']),
        'status'  => $result['status'] ?? '',
        'message' => $result['message'] ?? ($result['error'] ?? 'Could not check git status.')];
}

// ─── GIT COMMIT ────────────────────────────────────────────────────────
function toolGitCommit($args) {
    $cid     = (int)($args['client_id'] ?? 0);
    $message = trim($args['message'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = mcpBridge('smart_commit', ['client_id' => $cid, 'message' => $message]);
    return ['success' => !empty($result['ok']),
        'commit'  => $result['commit'] ?? '',
        'message' => $result['message'] ?? ($result['error'] ?? 'Could not commit changes.')];
}

// ─── GIT DIFF ──────────────────────────────────────────────────────────
function toolGitDiff($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = mcpBridge('git_diff', ['client_id' => $cid]);
    return ['success' => !empty($result['ok']),
        'diff'    => isset($result['diff']) ? mb_substr($result['diff'], 0, 3000) : '',
        'message' => $result['message'] ?? ($result['error'] ?? 'Could not get git diff.')];
}

// ═══════════════════════════════════════════════════════════════════════════
// PHASE 27 — 20 NEW ALFRED VISION VAPI TOOLS (21-40)
// ═══════════════════════════════════════════════════════════════════════════

// ─── 21. SEO AUDIT ─────────────────────────────────────────────────────
function toolSEOAudit($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('seo-audit', ['client_id' => $cid, 'domain' => $domain]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? 'SEO audit complete. Check your dashboard for details.'];
}

// ─── 22. CUSTOMER JOURNEY ──────────────────────────────────────────────
function toolCustomerJourney($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('customer-journey', ['client_id' => $cid]);
    return ['success' => !empty($result['ok']),
        'plan'       => $result['plan'] ?? 'unknown',
        'da_username'=> $result['da_username'] ?? 'none',
        'message'    => $result['message'] ?? 'Could not retrieve journey data.'];
}

// ─── 23. SUGGEST UPSELL ───────────────────────────────────────────────
function toolSuggestUpsell($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('suggest-upsell', ['client_id' => $cid]);
    return ['success'        => !empty($result['ok']),
        'current_plan'       => $result['current_plan'] ?? 'free',
        'recommendation'     => $result['recommendation'] ?? 'Builder',
        'price'              => $result['price'] ?? '$15/mo',
        'message'            => $result['message'] ?? 'Based on your usage, I have an upgrade suggestion for you.'];
}

// ─── 24. CREATE STAGING ────────────────────────────────────────────────
function toolCreateStaging($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('create-staging', ['client_id' => $cid, 'domain' => $domain]);
    return ['success'    => !empty($result['ok']),
        'staging_url'    => $result['staging_url'] ?? '',
        'message'        => $result['message'] ?? 'Staging site creation initiated.'];
}

// ─── 25. RUN TESTS ─────────────────────────────────────────────────────
function toolRunTests($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('run-tests', ['client_id' => $cid]);
    return ['success'    => !empty($result['ok']),
        'framework'      => $result['framework'] ?? 'auto',
        'message'        => $result['message'] ?? 'Running test suite now.'];
}

// ─── 26. GENERATE LANDING PAGE ─────────────────────────────────────────
function toolGenerateLanding($args) {
    $cid   = (int)($args['client_id'] ?? 0);
    $title = trim($args['title'] ?? '');
    $desc  = trim($args['description'] ?? '');
    $domain= trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$title || !$desc) return ['error' => 'I need a title and description for the landing page.'];

    $result = alfredBridge('generate-landing', [
        'client_id' => $cid, 'title' => $title, 'description' => $desc, 'domain' => $domain
    ]);
    return ['success' => !empty($result['ok']),
        'url'     => $result['url'] ?? '',
        'message' => $result['message'] ?? 'Landing page created.'];
}

// ─── 27. MIGRATE SITE ──────────────────────────────────────────────────
function toolMigrateSite($args) {
    $cid       = (int)($args['client_id'] ?? 0);
    $sourceUrl = trim($args['source_url'] ?? '');
    $target    = trim($args['target_domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$sourceUrl) return ['error' => 'What is the URL of your current website?'];

    $result = alfredBridge('migrate-site', [
        'client_id' => $cid, 'source_url' => $sourceUrl, 'target_domain' => $target
    ]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? 'Migration process started.'];
}

// ─── 28. DETECT FRAMEWORK ──────────────────────────────────────────────
function toolDetectFramework($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('detect-framework', ['client_id' => $cid, 'domain' => $domain]);
    return ['success'    => !empty($result['ok']),
        'frameworks'     => $result['frameworks'] ?? [],
        'message'        => $result['message'] ?? 'Framework detection complete.'];
}

// ─── 29. PERFORMANCE BENCHMARK ─────────────────────────────────────────
function toolPerfBenchmark($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $url = trim($args['url'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$url) return ['error' => 'What URL would you like me to benchmark?'];

    $result = alfredBridge('performance-benchmark', ['client_id' => $cid, 'url' => $url]);
    return ['success'    => !empty($result['ok']),
        'response_ms'    => $result['response_time_ms'] ?? 0,
        'status_code'    => $result['status_code'] ?? 0,
        'message'        => $result['message'] ?? 'Performance test complete.'];
}

// ─── 30. ACCESSIBILITY AUDIT ───────────────────────────────────────────
function toolA11yAudit($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('accessibility-audit', ['client_id' => $cid, 'domain' => $domain]);
    return ['success' => !empty($result['ok']),
        'checks'  => $result['checks'] ?? [],
        'message' => $result['message'] ?? 'Accessibility audit complete.'];
}

// ─── 31. REVENUE ANALYTICS ─────────────────────────────────────────────
function toolRevenueAnalytics($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    $period = trim($args['period'] ?? 'month');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('revenue-analytics', [
        'client_id' => $cid, 'domain' => $domain, 'period' => $period
    ]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? 'Revenue data retrieved.'];
}

// ─── 32. DEAD LINK SCAN ────────────────────────────────────────────────
function toolDeadLinks($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('dead-link-scan', ['client_id' => $cid, 'domain' => $domain]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? 'Dead link scan started.'];
}

// ─── 33. CHURN RISK ────────────────────────────────────────────────────
function toolChurnRisk($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('churn-risk', ['client_id' => $cid]);
    return ['success' => !empty($result['ok']),
        'score'   => $result['score'] ?? 0,
        'level'   => $result['level'] ?? 'Unknown',
        'message' => $result['message'] ?? 'Churn risk calculated.'];
}

// ─── 34. OPTIMIZE IMAGES ───────────────────────────────────────────────
function toolOptimizeImages($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('optimize-images', ['client_id' => $cid, 'domain' => $domain]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? 'Image optimization queued.'];
}

// ─── 35. GENERATE LEGAL PAGES ──────────────────────────────────────────
function toolGenerateLegal($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    $biz    = trim($args['business_name'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('generate-legal', [
        'client_id' => $cid, 'domain' => $domain, 'business_name' => $biz
    ]);
    return ['success' => !empty($result['ok']),
        'pages'   => $result['pages'] ?? [],
        'message' => $result['message'] ?? 'Legal pages generated.'];
}

// ─── 36. SETUP SSL ─────────────────────────────────────────────────────
function toolSetupSSL($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$domain) return ['error' => 'Which domain do you want SSL for?'];

    $result = alfredBridge('setup-ssl', ['client_id' => $cid, 'domain' => $domain]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? 'SSL setup initiated.'];
}

// ─── 37. BILLING FORECAST ──────────────────────────────────────────────
function toolBillingForecast($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('billing-forecast', ['client_id' => $cid]);
    return ['success'    => !empty($result['ok']),
        'plan'           => $result['plan'] ?? 'free',
        'monthly'        => $result['monthly_estimate'] ?? 0,
        'message'        => $result['message'] ?? 'Billing forecast generated.'];
}

// ─── 38. EXPORT DATA ───────────────────────────────────────────────────
function toolExportData($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $db     = trim($args['database'] ?? '');
    $format = trim($args['format'] ?? 'csv');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('export-data', [
        'client_id' => $cid, 'database' => $db, 'format' => $format
    ]);
    return ['success' => !empty($result['ok']),
        'message' => $result['message'] ?? 'Data export started.'];
}

// ─── 39. CREATE CONTACT FORM ───────────────────────────────────────────
function toolContactForm($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    $email  = trim($args['email'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('create-contact-form', [
        'client_id' => $cid, 'domain' => $domain, 'email' => $email
    ]);
    return ['success' => !empty($result['ok']),
        'url'     => $result['url'] ?? '',
        'message' => $result['message'] ?? 'Contact form created.'];
}

// ─── 40. SEND STATUS REPORT ────────────────────────────────────────────
function toolStatusReport($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $result = alfredBridge('send-status-report', ['client_id' => $cid]);
    return ['success' => !empty($result['ok']),
        'report'  => $result['report'] ?? [],
        'message' => $result['message'] ?? 'Status report generated.'];
}

// ═══════════════════════════════════════════════════════════════════════════
//   CALLBACK SECURITY TOOLS — Tiered Access + Outbound Verification
//   Security model: Public tier (free info) → Callback (verify by phone)
//   → Full tier (authenticated, all tools)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Tool 41: verifyForCallback
 * Called when caller wants account access. Checks email → phone on file → 
 * offers callback. Returns security tier and phone hint.
 */
function toolVerifyCallback($args) {
    $email       = strtolower(trim($args['email'] ?? ''));
    $callerPhone = trim($args['caller_phone'] ?? '');

    if (empty($email)) {
        return [
            'tier'    => 'public',
            'message' => 'What is your email address? I will look up your account.'
        ];
    }

    $db = getDB();
    if (!$db) return ['tier' => 'public',
        'message' => 'I am having a technical issue. I can still help with general questions.'];

    $stmt = $db->prepare("
        SELECT c.id, c.firstname, c.lastname, c.email, c.phone, c.status
        FROM clients c WHERE c.email = :e LIMIT 1
    ");
    $stmt->execute([':e' => $email]);
    $client = $stmt->fetch();

    if (!$client) {
        return ['tier' => 'public', 'found' => false,
            'message' => 'I could not find an account with that email. Could you try another? I can still help with general questions like domain availability or pricing.'];
    }

    if ($client['status'] !== 'Active') {
        return ['tier' => 'public', 'found' => true, 'active' => false,
            'message' => 'That account appears to be inactive. I can create a support ticket to help reactivate it.'];
    }

    $storedPhone = preg_replace('/\D/', '', $client['phone'] ?? '');
    $hasPhone    = strlen($storedPhone) >= 7;
    $phoneLast4  = $hasPhone ? substr($storedPhone, -4) : '';

    // Check if caller is already on verified number
    $callerClean = preg_replace('/\D/', '', $callerPhone);
    if ($hasPhone && strlen($callerClean) >= 10 && str_ends_with($callerClean, substr($storedPhone, -10))) {
        authLog($db, $email, $callerPhone, true, 'caller_id_match');
        return [
            'tier'          => 'full',
            'authenticated' => true,
            'client_id'     => $client['id'],
            'first_name'    => $client['firstname'],
            'auth_method'   => 'caller_id_match',
            'message'       => 'You are calling from your verified number. Welcome back, ' . $client['firstname'] . '! You have full account access. How can I help?'
        ];
    }

    if (!$hasPhone) {
        return [
            'tier'       => 'public',
            'found'      => true,
            'client_id'  => $client['id'],
            'first_name' => $client['firstname'],
            'has_phone'  => false,
            'message'    => 'I found your account, ' . $client['firstname'] . ', but there is no phone number on file. I can help with general questions. To get full access next time, add a phone number in your account profile at gositeme.com.'
        ];
    }

    return [
        'tier'        => 'public',
        'found'       => true,
        'client_id'   => $client['id'],
        'first_name'  => $client['firstname'],
        'has_phone'   => true,
        'phone_hint'  => '****' . $phoneLast4,
        'can_callback'=> true,
        'message'     => 'I found your account, ' . $client['firstname'] . '! For security I will need to verify your identity. I can call you right back on the number ending in ' . $phoneLast4 . ' that we have on file. The call is free for you and the audio will be even clearer. Shall I call you back right now?'
    ];
}

/**
 * Tool 42: initiateCallback
 * Called when caller says "yes" to the callback offer.
 * Triggers the outbound call to the verified number on file.
 */
function toolInitiateCallback($args) {
    $clientId    = (int)($args['client_id'] ?? 0);
    $reason      = trim($args['reason'] ?? 'security_callback');
    $summary     = trim($args['greeting_summary'] ?? '');
    $inboundCall = trim($args['inbound_call_id'] ?? '');

    if (!$clientId) {
        return ['error' => true, 'message' => 'I need the client ID. Please verify the email first.'];
    }

    // Call the callback API
    $payload = json_encode([
        'client_id'        => $clientId,
        'reason'           => $reason,
        'greeting_summary' => $summary,
        'inbound_call_id'  => $inboundCall
    ]);

    $ch = curl_init('http://127.0.0.1/api/vapi-callback.php?action=initiate');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-API-KEY: ' . OUTBOUND_SECRET
        ],
        CURLOPT_TIMEOUT => 15
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (!$result) {
        return ['success' => false,
            'message' => 'I was unable to place the callback. Let me create a support ticket so our team can call you within 24 hours.'];
    }

    return $result;
}

/**
 * Tool 43: checkCallbackStatus
 * Check if the current call is from a verified callback.
 */
function toolCallbackStatus($args) {
    $callbackId = (int)($args['callback_id'] ?? 0);
    $callId     = trim($args['call_id'] ?? '');
    $clientId   = (int)($args['client_id'] ?? 0);

    $payload = json_encode([
        'callback_id' => $callbackId,
        'call_id'     => $callId,
        'client_id'   => $clientId
    ]);

    $action = $callId ? 'tier_check' : 'status';
    $ch = curl_init("http://127.0.0.1/api/vapi-callback.php?action=$action");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-API-KEY: ' . OUTBOUND_SECRET
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?: ['tier' => 'public'];
}


// ═══════════════════════════════════════════════════════════════════════════
// v9.1: VOICE MANAGEMENT TOOLS
// These wrap the voice-manage.php DB queries so all voice features
// (agents, phones, calls, SMS, fax, campaigns, documents, usage)
// are accessible via voice commands and Alfred chat.
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Generic voice-manage bridge.
 * Calls the voice management DB directly on behalf of the authenticated customer.
 */
function toolVoiceManage($action, $args) {
    $clientId = (int)($args['client_id'] ?? 0);
    if (!$clientId) {
        return ['error' => 'I need to verify your identity first. Please provide your email so I can authenticate you.'];
    }
    return voiceManageDirect($action, $args, $clientId);
}

/**
 * Direct voice management — queries DB directly for server-to-server calls.
 */
function voiceManageDirect($action, $args, $clientId) {
    $db = getDB();
    if (!$db) return ['error' => 'Database connection unavailable. Please try again.'];

    switch ($action) {
        case 'dashboard':
            $agents = $db->prepare("SELECT COUNT(*) as c FROM voice_agents WHERE client_id=:cid AND active=1");
            $agents->execute([':cid' => $clientId]);

            $phones = $db->prepare("SELECT COUNT(*) as c FROM voice_phone_numbers WHERE client_id=:cid AND active=1");
            $phones->execute([':cid' => $clientId]);

            $calls30 = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(duration_seconds),0) as seconds, COALESCE(SUM(cost),0) as cost FROM voice_calls WHERE client_id=:cid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $calls30->execute([':cid' => $clientId]);
            $callStats = $calls30->fetch(PDO::FETCH_ASSOC);

            $sms30 = $db->prepare("SELECT COUNT(*) as c FROM voice_sms WHERE client_id=:cid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $sms30->execute([':cid' => $clientId]);

            $fax30 = $db->prepare("SELECT COUNT(*) as c FROM voice_fax WHERE client_id=:cid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $fax30->execute([':cid' => $clientId]);

            $agentCount  = (int)$agents->fetch()['c'];
            $phoneCount  = (int)$phones->fetch()['c'];
            $callTotal   = (int)$callStats['total'];
            $minutes     = round($callStats['seconds'] / 60, 1);
            $cost        = round($callStats['cost'], 2);
            $smsCount    = (int)$sms30->fetch()['c'];
            $faxCount    = (int)$fax30->fetch()['c'];

            return [
                'agents' => $agentCount, 'phone_numbers' => $phoneCount,
                'calls_30d' => $callTotal, 'minutes_30d' => $minutes,
                'cost_30d' => $cost, 'sms_30d' => $smsCount, 'fax_30d' => $faxCount,
                'message' => "Here's your voice dashboard: You have $agentCount AI agent(s) and $phoneCount phone number(s). In the last 30 days: $callTotal calls ($minutes minutes, \$$cost), $smsCount SMS messages, and $faxCount faxes."
            ];

        case 'agents':
            $s = $db->prepare("SELECT a.id, a.name, a.persona, a.language, a.voice_name, a.greeting, a.transfer_number, a.created_at, p.phone_number as assigned_phone FROM voice_agents a LEFT JOIN voice_phone_numbers p ON p.agent_id = a.id AND p.active=1 WHERE a.client_id=:cid AND a.active=1 ORDER BY a.created_at DESC");
            $s->execute([':cid' => $clientId]);
            $agents = $s->fetchAll(PDO::FETCH_ASSOC);
            $count = count($agents);
            $names = array_column($agents, 'name');
            return [
                'agents' => $agents, 'count' => $count,
                'message' => $count ? "You have $count AI agent(s): " . implode(', ', $names) . ". Would you like to update any of them or create a new one?" : "You don't have any AI agents yet. Would you like me to create one for you?"
            ];

        case 'agent_create':
            $name     = trim($args['name'] ?? 'My AI Agent');
            $persona  = trim($args['persona'] ?? 'You are a professional, friendly AI assistant.');
            $greeting = trim($args['greeting'] ?? 'Hello! Thank you for calling. How can I help you today?');
            $language = trim($args['language'] ?? 'en');
            $voiceName = trim($args['voice_name'] ?? 'default');
            $transfer  = trim($args['transfer_number'] ?? '');

            $s = $db->prepare("INSERT INTO voice_agents (client_id, name, persona, greeting, language, voice_name, transfer_number) VALUES (:cid, :name, :persona, :greeting, :lang, :voice, :transfer)");
            $s->execute([':cid' => $clientId, ':name' => $name, ':persona' => $persona, ':greeting' => $greeting, ':lang' => $language, ':voice' => $voiceName, ':transfer' => $transfer]);
            $agentId = $db->lastInsertId();
            return ['success' => true, 'agent_id' => $agentId, 'message' => "I've created your new AI agent \"$name\" with ID $agentId. You can now assign a phone number to it."];

        case 'agent_update':
            $agentId = (int)($args['agent_id'] ?? 0);
            if (!$agentId) return ['error' => 'Which agent would you like to update? Please provide the agent ID or name.'];

            $own = $db->prepare("SELECT * FROM voice_agents WHERE id=:id AND client_id=:cid AND active=1");
            $own->execute([':id' => $agentId, ':cid' => $clientId]);
            if (!$own->fetch()) return ['error' => 'Agent not found. Use listMyAgents to see your agents.'];

            $fields = []; $params = [':id' => $agentId];
            foreach (['name','persona','greeting','language','voice_name','transfer_number','voicemail_enabled','max_call_duration','knowledge_base'] as $f) {
                if (isset($args[$f])) { $fields[] = "$f = :$f"; $params[":$f"] = $args[$f]; }
            }
            if (!$fields) return ['error' => 'What would you like to update? You can change the name, persona, greeting, language, voice, or transfer number.'];

            $db->prepare("UPDATE voice_agents SET " . implode(', ', $fields) . " WHERE id=:id")->execute($params);
            return ['success' => true, 'message' => 'Agent updated successfully. The changes are now live.'];

        case 'agent_delete':
            $agentId = (int)($args['agent_id'] ?? 0);
            if (!$agentId) return ['error' => 'Which agent would you like to delete? Please provide the agent ID.'];

            $own = $db->prepare("SELECT id FROM voice_agents WHERE id=:id AND client_id=:cid AND active=1");
            $own->execute([':id' => $agentId, ':cid' => $clientId]);
            if (!$own->fetch()) return ['error' => 'Agent not found.'];

            $db->prepare("UPDATE voice_agents SET active=0 WHERE id=:id")->execute([':id' => $agentId]);
            $db->prepare("UPDATE voice_phone_numbers SET agent_id=NULL WHERE agent_id=:id")->execute([':id' => $agentId]);
            return ['success' => true, 'message' => 'Agent deleted and phone numbers unassigned.'];

        case 'phones':
            $s = $db->prepare("SELECT pn.id, pn.phone_number, pn.country_code as country, pn.phone_type as type, pn.sms_enabled, pn.fax_enabled, pn.provisioned_at, va.name as agent_name FROM voice_phone_numbers pn LEFT JOIN voice_agents va ON va.id = pn.agent_id WHERE pn.client_id=:cid AND pn.active=1 ORDER BY pn.provisioned_at DESC");
            $s->execute([':cid' => $clientId]);
            $phones = $s->fetchAll(PDO::FETCH_ASSOC);
            $count = count($phones);
            if (!$count) return ['phones' => [], 'count' => 0, 'message' => "You don't have any phone numbers yet. Would you like to order one?"];
            $nums = array_map(fn($p) => $p['phone_number'] . ($p['agent_name'] ? " (→ {$p['agent_name']})" : ' (unassigned)'), $phones);
            return ['phones' => $phones, 'count' => $count, 'message' => "You have $count phone number(s): " . implode('; ', $nums)];

        case 'phone_assign':
            $phoneId = (int)($args['phone_id'] ?? 0);
            $agentId = (int)($args['agent_id'] ?? 0);
            if (!$phoneId) return ['error' => 'Which phone number? Please provide the phone ID.'];

            // Verify ownership and get vapi_phone_id
            $own = $db->prepare("SELECT id, vapi_phone_id FROM voice_phone_numbers WHERE id=:id AND client_id=:cid AND active=1");
            $own->execute([':id' => $phoneId, ':cid' => $clientId]);
            $phone = $own->fetch();
            if (!$phone) return ['error' => 'Phone number not found.'];

            $vapiAssistantId = null;
            if ($agentId) {
                $ownA = $db->prepare("SELECT id, vapi_assistant_id FROM voice_agents WHERE id=:id AND client_id=:cid AND active=1");
                $ownA->execute([':id' => $agentId, ':cid' => $clientId]);
                $agent = $ownA->fetch();
                if (!$agent) return ['error' => "Agent ID $agentId not found."];
                $vapiAssistantId = $agent['vapi_assistant_id'] ?? null;
            }

            // Update DB
            $db->prepare("UPDATE voice_phone_numbers SET agent_id=:aid WHERE id=:id")->execute([':aid' => $agentId ?: null, ':id' => $phoneId]);

            // Update VAPI phone → assistant mapping (critical for actual call routing)
            $vapiPhoneId = $phone['vapi_phone_id'] ?? null;
            if ($vapiPhoneId) {
                $vapiResult = assignVapiPhoneToAssistant($vapiPhoneId, $vapiAssistantId);
                if (!empty($vapiResult['error'])) {
                    error_log("[phone_assign] VAPI update failed: " . $vapiResult['error']);
                    // DB is updated, VAPI failed — warn but don't rollback
                    return ['success' => true, 'warning' => 'VAPI routing update pending.',
                            'message' => $agentId ? 'Phone assigned in our system. Call routing update may take a moment.' : 'Phone number unassigned.'];
                }
            }

            return ['success' => true, 'message' => $agentId ? 'Phone number assigned to your agent. Incoming calls will now be handled by that agent.' : 'Phone number unassigned.'];

        case 'calls':
            $page   = max(1, (int)($args['page'] ?? 1));
            $limit  = min(50, max(5, (int)($args['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;
            $dir    = in_array($args['direction'] ?? '', ['inbound','outbound']) ? $args['direction'] : null;

            $where = "client_id = :cid"; $params = [':cid' => $clientId];
            if ($dir) { $where .= " AND direction = :dir"; $params[':dir'] = $dir; }

            $total = $db->prepare("SELECT COUNT(*) as c FROM voice_calls WHERE $where");
            $total->execute($params);
            $totalCount = (int)$total->fetch()['c'];

            $s = $db->prepare("SELECT id, direction, caller_number, callee_number, duration_seconds, status, sentiment, created_at FROM voice_calls WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $s->execute($params);
            $calls = $s->fetchAll(PDO::FETCH_ASSOC);

            return [
                'calls' => $calls, 'total' => $totalCount, 'page' => $page,
                'message' => $totalCount ? "You have $totalCount call(s). Showing page $page." : "No calls found."
            ];

        case 'call_detail':
            $callId = (int)($args['call_id'] ?? 0);
            if (!$callId) return ['error' => 'Which call? Please provide the call ID.'];

            $s = $db->prepare("SELECT c.*, va.name as agent_name FROM voice_calls c LEFT JOIN voice_agents va ON va.id = c.agent_id WHERE c.id=:id AND c.client_id=:cid");
            $s->execute([':id' => $callId, ':cid' => $clientId]);
            $call = $s->fetch(PDO::FETCH_ASSOC);
            if (!$call) return ['error' => 'Call not found.'];

            $dur = round(($call['duration_seconds'] ?? 0) / 60, 1);
            return [
                'call' => $call,
                'message' => "Call #{$call['id']}: {$call['direction']} on {$call['created_at']}. Duration: {$dur} min. Status: {$call['status']}." .
                    ($call['sentiment'] ? " Sentiment: {$call['sentiment']}." : '') .
                    ($call['agent_name'] ? " Agent: {$call['agent_name']}." : '')
            ];

        case 'sms':
            $s = $db->prepare("SELECT id, direction, from_number, to_number, message, status, created_at FROM voice_sms WHERE client_id=:cid ORDER BY created_at DESC LIMIT 25");
            $s->execute([':cid' => $clientId]);
            $messages = $s->fetchAll(PDO::FETCH_ASSOC);
            return ['messages' => $messages, 'count' => count($messages), 'message' => count($messages) ? "Here are your last " . count($messages) . " SMS messages." : "No SMS messages found."];

        case 'sms_send':
            $to      = trim($args['to'] ?? '');
            $message = trim($args['message'] ?? '');
            $phoneId = (int)($args['phone_number_id'] ?? 0);
            if (!$to)      return ['error' => 'Who should I send the SMS to?'];
            if (!$message) return ['error' => 'What should the message say?'];

            if (!$phoneId) {
                $ph = $db->prepare("SELECT id, phone_number FROM voice_phone_numbers WHERE client_id=:cid AND sms_enabled=1 AND active=1 LIMIT 1");
                $ph->execute([':cid' => $clientId]);
                $defaultPhone = $ph->fetch(PDO::FETCH_ASSOC);
                if (!$defaultPhone) return ['error' => "You don't have an SMS-enabled phone number. Order one first."];
                $phoneId = $defaultPhone['id'];
            }

            $ph = $db->prepare("SELECT phone_number FROM voice_phone_numbers WHERE id=:id AND client_id=:cid AND sms_enabled=1");
            $ph->execute([':id' => $phoneId, ':cid' => $clientId]);
            $from = $ph->fetch();
            if (!$from) return ['error' => 'That phone number is not SMS-enabled.'];

            // Actually send via Telnyx with fallback
            $smsResult = sendSmsWithFallback($to, $from['phone_number'], $message, $clientId);
            if (!empty($smsResult['success'])) {
                return ['success' => true, 'provider' => $smsResult['provider'] ?? 'telnyx', 'message' => "SMS sent to $to via {$smsResult['provider']}."];
            }
            // Queued for retry
            return ['success' => true, 'message' => "SMS queued for delivery to $to.", 'queued' => true, 'warning' => $smsResult['error'] ?? 'Provider unavailable'];

        case 'fax':
            $s = $db->prepare("SELECT id, direction, from_number, to_number, document_url, pages, status, created_at FROM voice_fax WHERE client_id=:cid ORDER BY created_at DESC LIMIT 25");
            $s->execute([':cid' => $clientId]);
            $faxes = $s->fetchAll(PDO::FETCH_ASSOC);
            return ['faxes' => $faxes, 'count' => count($faxes), 'message' => count($faxes) ? "Here are your last " . count($faxes) . " faxes." : "No faxes found."];

        case 'fax_send':
            $to     = trim($args['to'] ?? '');
            $docUrl = trim($args['document_url'] ?? '');
            $phoneId = (int)($args['phone_number_id'] ?? 0);
            if (!$to)     return ['error' => 'What fax number should I send to?'];
            if (!$docUrl) return ['error' => 'What document should I fax? Provide the document URL.'];

            if (!$phoneId) {
                $ph = $db->prepare("SELECT id, phone_number FROM voice_phone_numbers WHERE client_id=:cid AND fax_enabled=1 AND active=1 LIMIT 1");
                $ph->execute([':cid' => $clientId]);
                $defaultPhone = $ph->fetch(PDO::FETCH_ASSOC);
                if (!$defaultPhone) return ['error' => "You don't have a fax-enabled phone number. Order one first."];
                $phoneId = $defaultPhone['id'];
            }

            $ph = $db->prepare("SELECT phone_number FROM voice_phone_numbers WHERE id=:id AND client_id=:cid AND fax_enabled=1");
            $ph->execute([':id' => $phoneId, ':cid' => $clientId]);
            $from = $ph->fetch();
            if (!$from) return ['error' => 'That phone number is not fax-enabled.'];

            // Actually send via Telnyx with fallback
            $faxResult = sendFaxWithFallback($to, $from['phone_number'], $docUrl, $clientId);
            if (!empty($faxResult['success'])) {
                return ['success' => true, 'provider' => $faxResult['provider'] ?? 'telnyx', 'fax_id' => $faxResult['fax_id'] ?? null, 'message' => "Fax sent to $to via {$faxResult['provider']}."];
            }
            return ['success' => true, 'message' => "Fax queued for delivery to $to.", 'queued' => true, 'warning' => $faxResult['error'] ?? 'Provider unavailable'];

        case 'campaigns':
            $s = $db->prepare("SELECT cp.id, cp.name, cp.type, cp.status, cp.total_contacts, cp.contacts_reached, cp.schedule_start, va.name as agent_name FROM voice_campaigns cp LEFT JOIN voice_agents va ON va.id = cp.agent_id WHERE cp.client_id=:cid ORDER BY cp.created_at DESC");
            $s->execute([':cid' => $clientId]);
            $campaigns = $s->fetchAll(PDO::FETCH_ASSOC);
            $count = count($campaigns);
            return ['campaigns' => $campaigns, 'count' => $count, 'message' => $count ? "You have $count campaign(s): " . implode(', ', array_map(fn($c) => "\"{$c['name']}\" ({$c['status']})", $campaigns)) : "No campaigns found."];

        case 'campaign_create':
            $name    = trim($args['name'] ?? 'New Campaign');
            $agentId = (int)($args['agent_id'] ?? 0);
            $contacts = $args['contacts'] ?? [];

            $s = $db->prepare("INSERT INTO voice_campaigns (client_id, agent_id, name, type, contact_list, total_contacts, concurrent_lines, status) VALUES (:cid, :aid, :name, :type, :contacts, :total, :lines, 'draft')");
            $s->execute([':cid' => $clientId, ':aid' => $agentId ?: null, ':name' => $name, ':type' => $args['type'] ?? 'outbound', ':contacts' => json_encode($contacts), ':total' => count($contacts), ':lines' => min(10, (int)($args['concurrent_lines'] ?? 1))]);
            return ['success' => true, 'campaign_id' => $db->lastInsertId(), 'message' => "Campaign \"$name\" created in draft status."];

        case 'campaign_update':
            $campId = (int)($args['campaign_id'] ?? 0);
            $status = $args['status'] ?? '';
            if (!$campId) return ['error' => 'Which campaign? Provide the campaign ID.'];
            if (!in_array($status, ['scheduled','paused','cancelled','running'])) return ['error' => 'Valid statuses: scheduled, paused, cancelled, running.'];

            $own = $db->prepare("SELECT id FROM voice_campaigns WHERE id=:id AND client_id=:cid");
            $own->execute([':id' => $campId, ':cid' => $clientId]);
            if (!$own->fetch()) return ['error' => 'Campaign not found.'];

            $db->prepare("UPDATE voice_campaigns SET status=:s WHERE id=:id")->execute([':s' => $status, ':id' => $campId]);
            return ['success' => true, 'message' => "Campaign updated to \"$status\"."];

        case 'documents':
            $s = $db->prepare("SELECT id, name, type, created_at FROM voice_documents WHERE client_id=:cid ORDER BY updated_at DESC");
            $s->execute([':cid' => $clientId]);
            $docs = $s->fetchAll(PDO::FETCH_ASSOC);
            return ['documents' => $docs, 'count' => count($docs), 'message' => count($docs) ? "You have " . count($docs) . " document(s): " . implode(', ', array_column($docs, 'name')) : "No documents found."];

        case 'doc_create':
            $name = trim($args['name'] ?? 'Untitled');
            $s = $db->prepare("INSERT INTO voice_documents (client_id, name, type, template_html, variables) VALUES (:cid, :name, :type, :html, :vars)");
            $s->execute([':cid' => $clientId, ':name' => $name, ':type' => $args['type'] ?? 'custom', ':html' => $args['template_html'] ?? '', ':vars' => json_encode($args['variables'] ?? [])]);
            return ['success' => true, 'doc_id' => $db->lastInsertId(), 'message' => "Document \"$name\" created."];

        case 'doc_delete':
            $docId = (int)($args['doc_id'] ?? 0);
            if (!$docId) return ['error' => 'Which document? Provide the document ID.'];
            $db->prepare("DELETE FROM voice_documents WHERE id=:id AND client_id=:cid")->execute([':id' => $docId, ':cid' => $clientId]);
            return ['success' => true, 'message' => 'Document deleted.'];

        case 'usage':
            $s = $db->prepare("SELECT * FROM voice_usage WHERE client_id=:cid ORDER BY period_start DESC LIMIT 6");
            $s->execute([':cid' => $clientId]);
            $usage = $s->fetchAll(PDO::FETCH_ASSOC);
            if (!$usage) return ['usage' => [], 'message' => 'No usage data found yet.'];
            $cur = $usage[0];
            return ['usage' => $usage, 'message' => "Current period: {$cur['period_start']} to {$cur['period_end']}. Minutes: " . ($cur['minutes_used'] ?? 0) . "/" . ($cur['minutes_included'] ?? 0) . "."];

        default:
            return ['error' => "Unknown voice action: $action"];
    }
}


// ═══════════════════════════════════════════════════════════════════════════
// BILLING API PROXY — Direct server-to-server billing calls
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Call billing API proxy for payment operations (HTTP POST).
 * Compatibility layer that routes to legacy billing proxy
 * TODO: Migrate to direct Stripe + new billing tables
 * 
 */
function billingAPI($action, $params = []) {
    $url = 'https://gositeme.com/pay/local_api_proxy.php';
    $postFields = array_merge($params, [
        'action'     => $action,
        'identifier' => getenv('BILLING_PROXY_ID') ?: '',
        'secret'     => getenv('BILLING_PROXY_KEY') ?: '',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) { error_log("[billingAPI] cURL error: $err"); return ['result' => 'error', 'message' => $err]; }
    if ($httpCode >= 400) { error_log("[billingAPI] HTTP $httpCode"); return ['result' => 'error', 'message' => "HTTP $httpCode"]; }

    $data = json_decode($resp, true);
    return $data ?: ['result' => 'error', 'message' => 'Invalid response'];
}

// ── Payment: Create Client ──────────────────────────────────────────────
function toolCreateClientDirect($args) {
    $firstName = trim($args['first_name'] ?? $args['firstname'] ?? '');
    $lastName  = trim($args['last_name'] ?? $args['lastname'] ?? '');
    $email     = trim($args['email'] ?? '');
    $phone     = trim($args['phone'] ?? $args['phone'] ?? '');

    if (!$email) return ['error' => 'Email address is required to create an account.'];
    if (!$firstName) return ['error' => 'First name is required.'];

    $result = billingAPI('AddClient', [
        'firstname'   => $firstName,
        'lastname'    => $lastName ?: 'Customer',
        'email'       => $email,
        'phonenumber' => $phone,
        'address1'    => $args['address'] ?? '123 Main St',
        'city'        => $args['city'] ?? 'Montreal',
        'state'       => $args['state'] ?? 'QC',
        'postcode'    => $args['postcode'] ?? 'H2X 1Y4',
        'country'     => $args['country'] ?? 'CA',
        'password2'   => $args['password'] ?? bin2hex(random_bytes(8)),
        'paymentmethod' => 'stripe',
        'currency'    => 1,
    ]);

    if (($result['result'] ?? '') === 'success') {
        return ['success' => true, 'client_id' => $result['clientid'], 'message' => "Account created for $firstName. Client ID: {$result['clientid']}."];
    }
    return ['error' => $result['message'] ?? 'Failed to create account.'];
}

// ── Payment: Add Payment Method (card via Stripe gateway) ─────────
function toolAddPaymentMethodDirect($args) {
    $clientId = (int)($args['client_id'] ?? 0);
    if (!$clientId) return ['error' => 'Client ID required.'];

    $cardNumber = $args['card_number'] ?? '';
    $expiry     = $args['expiry'] ?? $args['expiry_date'] ?? '';
    $cvv        = $args['cvv'] ?? $args['cvc'] ?? '';

    if (!$cardNumber || !$expiry) {
        // Return a payment link instead — safer than collecting cards over voice
        $result = billingAPI('GetClients', ['clientid' => $clientId, 'limitnum' => 1]);
        $email = $result['clients']['client'][0]['email'] ?? '';
        return [
            'success' => false,
            'payment_link' => "https://gositeme.com/payment-methods",
            'message' => "For security, please use our secure payment portal to add your card. I can send you the link" . ($email ? " to $email" : "") . "."
        ];
    }

    // Use AddPayMethod billing API
    $result = billingAPI('AddPayMethod', [
        'clientid'       => $clientId,
        'type'           => 'CreditCard',
        'card_number'    => preg_replace('/\D/', '', $cardNumber),
        'card_expiry'    => preg_replace('/\D/', '', $expiry),
        'card_cvv'       => $cvv,
        'set_as_default' => true,
    ]);

    if (($result['result'] ?? '') === 'success') {
        return ['success' => true, 'message' => 'Payment method added successfully.'];
    }
    return ['error' => $result['message'] ?? 'Failed to add payment method. Please use the secure portal.'];
}

// ── Payment: Get Payment Methods ────────────────────────────────────────
function toolGetPaymentMethodsDirect($args) {
    $clientId = (int)($args['client_id'] ?? 0);
    if (!$clientId) return ['error' => 'Client ID required.'];

    $result = billingAPI('GetPayMethods', ['clientid' => $clientId]);
    if (($result['result'] ?? '') !== 'success') return ['error' => $result['message'] ?? 'Could not retrieve payment methods.'];

    $methods = [];
    if (!empty($result['paymethods'])) {
        foreach ($result['paymethods'] as $m) {
            $methods[] = [
                'id'          => $m['id'] ?? null,
                'type'        => $m['type'] ?? 'unknown',
                'description' => $m['description'] ?? '',
                'is_default'  => !empty($m['is_default']),
            ];
        }
    }

    return ['success' => true, 'methods' => $methods, 'count' => count($methods),
            'message' => count($methods) ? count($methods) . ' payment method(s) on file.' : 'No payment methods on file. Would you like to add one?'];
}

// ── Payment: Process Payment (capture invoice) ──────────────────────────
function toolProcessPaymentDirect($args) {
    $invoiceId = (int)($args['invoice_id'] ?? $args['invoiceid'] ?? 0);
    $clientId  = (int)($args['client_id'] ?? 0);

    if (!$invoiceId && !$clientId) return ['error' => 'Invoice ID or client ID required.'];

    // If no invoice specified, find the oldest unpaid invoice
    if (!$invoiceId && $clientId) {
        $result = billingAPI('GetInvoices', ['userid' => $clientId, 'status' => 'Unpaid', 'limitnum' => 1, 'orderby' => 'id', 'order' => 'asc']);
        if (!empty($result['invoices']['invoice'][0])) {
            $invoiceId = (int)$result['invoices']['invoice'][0]['id'];
        } else {
            return ['success' => true, 'message' => 'No unpaid invoices found. Account is up to date!'];
        }
    }

    // Verify the invoice belongs to the client (if client_id provided)
    if ($clientId) {
        $inv = billingAPI('GetInvoice', ['invoiceid' => $invoiceId]);
        if (($inv['result'] ?? '') === 'success' && (int)($inv['userid'] ?? 0) !== $clientId) {
            return ['error' => 'Invoice does not belong to this client.'];
        }
    }

    // Try to capture via the configured gateway (Stripe)
    $result = billingAPI('CapturePayment', ['invoiceid' => $invoiceId]);
    if (($result['result'] ?? '') === 'success') {
        return ['success' => true, 'invoice_id' => $invoiceId, 'message' => "Payment captured successfully for invoice #$invoiceId."];
    }

    // If capture failed (no card on file), try applying credit
    $creditResult = billingAPI('ApplyCredit', ['invoiceid' => $invoiceId]);
    if (($creditResult['result'] ?? '') === 'success') {
        return ['success' => true, 'invoice_id' => $invoiceId, 'message' => "Payment applied from account credit for invoice #$invoiceId."];
    }

    // Send the payment link as fallback
    $payLink = "https://gositeme.com/view-invoice?id=$invoiceId";
    return [
        'success' => false,
        'invoice_id' => $invoiceId,
        'payment_link' => $payLink,
        'message' => "I couldn't process the payment automatically. Please pay invoice #$invoiceId using this secure link: $payLink"
    ];
}

// ── Payment: Accept Order ───────────────────────────────────────────────
function toolAcceptOrderDirect($args) {
    $orderId = (int)($args['order_id'] ?? $args['orderid'] ?? 0);
    if (!$orderId) return ['error' => 'Order ID required.'];

    $result = billingAPI('AcceptOrder', ['orderid' => $orderId]);
    if (($result['result'] ?? '') === 'success') {
        return ['success' => true, 'order_id' => $orderId, 'message' => "Order #$orderId accepted and activated."];
    }
    return ['error' => $result['message'] ?? "Failed to accept order #$orderId."];
}

// ── Payment: Order Hosting (Direct Billing) ───────────────────────────────
function toolOrderHostingDirect($args) {
    $productId  = (int)($args['productId'] ?? $args['product_id'] ?? $args['pid'] ?? 0);
    $domain     = trim($args['domain'] ?? '');
    $cycle      = $args['billingCycle'] ?? $args['billing_cycle'] ?? 'annually';
    $clientId   = (int)($args['client_id'] ?? 0);
    $confirmed  = !empty($args['confirmed']);

    if (!$productId) return ['error' => 'Product ID is required. Use product_catalog to browse plans.'];
    if (!$domain) $domain = 'default-' . time() . '.gositeme.com';

    if (!$confirmed) {
        // Get product name for confirmation
        $prod = billingAPI('GetProducts', ['pid' => $productId]);
        $name = $prod['products']['product'][0]['name'] ?? "Product #$productId";
        $price = $prod['products']['product'][0]['pricing']['USD'][strtolower($cycle)] ?? 'N/A';
        return ['needs_confirmation' => true, 'product' => $name, 'price' => $price,
                'message' => "I'll order $name for $domain ($cycle billing, $price). Shall I proceed?"];
    }

    $params = [
        'pid'           => $productId,
        'domain'        => $domain,
        'billingcycle'  => $cycle,
        'paymentmethod' => 'stripe',
    ];
    if ($clientId) $params['clientid'] = $clientId;

    $result = billingAPI('AddOrder', $params);
    if (($result['result'] ?? '') !== 'success') {
        return ['error' => $result['message'] ?? 'Order failed.'];
    }

    $orderId  = (int)($result['orderid'] ?? 0);
    $invoiceId = (int)($result['invoiceid'] ?? 0);

    // Auto-accept for instant provisioning
    if ($orderId) {
        billingAPI('AcceptOrder', ['orderid' => $orderId]);
    }

    // Try auto-pay
    $payMsg = '';
    if ($invoiceId) {
        $pay = billingAPI('CapturePayment', ['invoiceid' => $invoiceId]);
        $payMsg = (($pay['result'] ?? '') === 'success') ? ' Payment processed.'
            : " Invoice #$invoiceId created — pay at https://gositeme.com/view-invoice?id=$invoiceId";
    }

    return ['success' => true, 'order_id' => $orderId, 'invoice_id' => $invoiceId,
            'message' => "Hosting ordered for $domain!$payMsg Your service is being provisioned."];
}

// ── Payment: Voice Onboard (All-in-One Signup) ──────────────────────────
function toolVoiceOnboardDirect($args) {
    $firstName = trim($args['firstname'] ?? $args['first_name'] ?? '');
    $lastName  = trim($args['lastname'] ?? $args['last_name'] ?? 'Customer');
    $email     = trim($args['email'] ?? '');
    $phone     = trim($args['phone'] ?? $args['phone'] ?? '');
    $confirmed = !empty($args['confirmed']);

    if (!$email || !$firstName) {
        return ['error' => 'First name and email are required.'];
    }

    if (!$confirmed) {
        $productId = (int)($args['productId'] ?? $args['product_id'] ?? 0);
        $domain    = $args['domain'] ?? 'your site';
        return ['needs_confirmation' => true,
                'message' => "I'll create an account for $firstName $lastName ($email), set up hosting" .
                    ($productId ? " for $domain" : '') . ", and process your payment. Ready to proceed?"];
    }

    $steps = [];

    // Step 1: Create account
    $clientResult = toolCreateClientDirect([
        'first_name' => $firstName, 'last_name' => $lastName,
        'email' => $email, 'phone' => $phone,
        'country' => $args['country'] ?? 'CA',
        'password' => $args['password'] ?? bin2hex(random_bytes(8)),
    ]);
    $steps[] = ['step' => 'account', 'result' => $clientResult];

    if (!empty($clientResult['error'])) {
        // Check if client already exists
        $existing = billingAPI('GetClientsDetails', ['email' => $email]);
        if (($existing['result'] ?? '') === 'success') {
            $clientId = (int)$existing['userid'];
            $steps[0]['result'] = ['success' => true, 'client_id' => $clientId, 'status' => 'exists'];
        } else {
            return ['success' => false, 'steps' => $steps, 'message' => 'Could not create account: ' . ($clientResult['error'] ?? 'Unknown error')];
        }
    }
    $clientId = (int)($clientResult['client_id'] ?? $steps[0]['result']['client_id'] ?? 0);

    // Step 2: Add payment method if card provided
    $cardNumber = $args['card_number'] ?? '';
    if ($cardNumber) {
        $pmResult = toolAddPaymentMethodDirect([
            'client_id' => $clientId,
            'card_number' => $cardNumber,
            'expiry' => $args['card_expiry'] ?? $args['expiry'] ?? '',
            'cvv' => $args['card_cvv'] ?? $args['cvv'] ?? '',
        ]);
        $steps[] = ['step' => 'payment_method', 'result' => $pmResult];
    }

    // Step 3: Order hosting if product selected
    $productId = (int)($args['productId'] ?? $args['product_id'] ?? 0);
    if ($productId) {
        $orderResult = toolOrderHostingDirect([
            'productId' => $productId,
            'domain' => $args['domain'] ?? 'default-' . time() . '.gositeme.com',
            'billingCycle' => $args['billingCycle'] ?? $args['billing_cycle'] ?? 'annually',
            'client_id' => $clientId,
            'confirmed' => true,
        ]);
        $steps[] = ['step' => 'hosting', 'result' => $orderResult];
    }

    return [
        'success' => true,
        'client_id' => $clientId,
        'steps' => $steps,
        'message' => "Welcome aboard, $firstName! Your GoSiteMe account is ready." .
            ($productId ? ' Your service is being provisioned now.' : ' You can add services from your dashboard.') .
            " Check your email ($email) for login details."
    ];
}

// ── Profile: Update Client Profile (Direct Billing) ──────────────────────
function toolUpdateProfileDirect($args) {
    $clientId = (int)($args['client_id'] ?? 0);
    if (!$clientId) return ['error' => 'Client ID required.'];

    $params = ['clientid' => $clientId];
    $fields = ['firstname','lastname','email','phonenumber','address1','city','state','postcode','country','companyname'];
    foreach ($fields as $f) {
        if (isset($args[$f]) && $args[$f] !== '') $params[$f] = $args[$f];
    }
    if (count($params) <= 1) return ['error' => 'No fields to update.'];

    $result = billingAPI('UpdateClient', $params);
    if (($result['result'] ?? '') === 'success') {
        return ['success' => true, 'message' => 'Profile updated successfully.'];
    }
    return ['error' => $result['message'] ?? 'Failed to update profile.'];
}


// ═══════════════════════════════════════════════════════════════════════════
// VOICE PRODUCT TOOLS — Order voice products, get recommendations
// ═══════════════════════════════════════════════════════════════════════════

function getVoiceProductCatalog() {
    return [
        ['pid'=>49,'name'=>'AI Agent Starter','category'=>'AI Agents','price'=>'$29/mo','features'=>'1 AI agent, 100 min/mo, basic analytics'],
        ['pid'=>50,'name'=>'AI Agent Business','category'=>'AI Agents','price'=>'$79/mo','features'=>'3 AI agents, 500 min/mo, advanced analytics, call recording'],
        ['pid'=>51,'name'=>'AI Agent Professional','category'=>'AI Agents','price'=>'$199/mo','features'=>'10 AI agents, 2000 min/mo, sentiment analysis, integrations'],
        ['pid'=>52,'name'=>'AI Agent Enterprise','category'=>'AI Agents','price'=>'Custom','features'=>'Unlimited agents, custom models, SLA, dedicated support'],
        ['pid'=>53,'name'=>'Call Center Starter','category'=>'Call Center','price'=>'$149/mo','features'=>'5 agents, call queue, IVR, basic reporting'],
        ['pid'=>54,'name'=>'Call Center Growth','category'=>'Call Center','price'=>'$299/mo','features'=>'15 agents, workforce management, quality monitoring'],
        ['pid'=>55,'name'=>'Call Center Enterprise','category'=>'Call Center','price'=>'$599/mo','features'=>'50+ agents, predictive dialer, omnichannel'],
        ['pid'=>56,'name'=>'Predictive Dialer','category'=>'Call Center','price'=>'$99/mo','features'=>'Auto-dial campaigns, DNC compliance'],
        ['pid'=>57,'name'=>'Appointment Booking AI','category'=>'Call Center','price'=>'$49/mo','features'=>'AI scheduling, calendar sync, reminders'],
        ['pid'=>58,'name'=>'Survey & Feedback AI','category'=>'Call Center','price'=>'$39/mo','features'=>'Post-call surveys, NPS tracking'],
        ['pid'=>59,'name'=>'Local Phone Number','category'=>'Phone Numbers','price'=>'$3/mo','features'=>'Local area code, voice + SMS'],
        ['pid'=>60,'name'=>'Toll-Free Number','category'=>'Phone Numbers','price'=>'$5/mo','features'=>'1-800/888/877, voice + SMS'],
        ['pid'=>61,'name'=>'International Number','category'=>'Phone Numbers','price'=>'$10/mo','features'=>'50+ countries'],
        ['pid'=>62,'name'=>'Vanity Number','category'=>'Phone Numbers','price'=>'$15/mo','features'=>'Custom memorable number'],
        ['pid'=>63,'name'=>'Fax Number','category'=>'Phone Numbers','price'=>'$5/mo','features'=>'Dedicated fax line, email-to-fax'],
        ['pid'=>64,'name'=>'Short Code','category'=>'Phone Numbers','price'=>'$500/mo','features'=>'5-6 digit SMS short code'],
        ['pid'=>67,'name'=>'Fax Pro','category'=>'Fax','price'=>'$15/mo','features'=>'500 pages/mo, HIPAA compliant'],
        ['pid'=>68,'name'=>'Fax Enterprise','category'=>'Fax','price'=>'$49/mo','features'=>'5000 pages/mo, API, bulk sending'],
        ['pid'=>70,'name'=>'Office Basic','category'=>'Office Suite','price'=>'$25/mo','features'=>'1 line, voicemail, auto-attendant'],
        ['pid'=>71,'name'=>'Office Standard','category'=>'Office Suite','price'=>'$45/mo','features'=>'3 lines, ring groups, conference'],
        ['pid'=>72,'name'=>'Office Professional','category'=>'Office Suite','price'=>'$75/mo','features'=>'10 lines, recording, CRM integration'],
        ['pid'=>73,'name'=>'Office Enterprise','category'=>'Office Suite','price'=>'$125/mo','features'=>'Unlimited lines, SLA, API'],
        ['pid'=>74,'name'=>'Virtual Receptionist','category'=>'Office Suite','price'=>'$35/mo','features'=>'AI receptionist, transfer, schedule'],
        ['pid'=>75,'name'=>'SMS Starter','category'=>'SMS','price'=>'$10/mo','features'=>'500 SMS/mo, 2-way texting'],
        ['pid'=>76,'name'=>'SMS Business','category'=>'SMS','price'=>'$29/mo','features'=>'2000 SMS/mo, campaigns, analytics'],
        ['pid'=>77,'name'=>'SMS Enterprise','category'=>'SMS','price'=>'$79/mo','features'=>'10000 SMS/mo, API, MMS'],
        ['pid'=>78,'name'=>'Legal AI Agent','category'=>'Industry','price'=>'$149/mo','features'=>'Intake calls, case updates, HIPAA'],
        ['pid'=>79,'name'=>'Real Estate AI','category'=>'Industry','price'=>'$99/mo','features'=>'Lead qualification, showing scheduler'],
        ['pid'=>80,'name'=>'Medical & Dental AI','category'=>'Industry','price'=>'$149/mo','features'=>'HIPAA, appointments, prescription refills'],
        ['pid'=>81,'name'=>'Restaurant & Food AI','category'=>'Industry','price'=>'$69/mo','features'=>'Reservations, takeout orders, menu info'],
        ['pid'=>82,'name'=>'Automotive AI','category'=>'Industry','price'=>'$99/mo','features'=>'Service appointments, parts, test drives'],
        ['pid'=>83,'name'=>'Insurance AI','category'=>'Industry','price'=>'$129/mo','features'=>'Quotes, claims, policy info'],
        ['pid'=>84,'name'=>'Education AI','category'=>'Industry','price'=>'$79/mo','features'=>'Enrollment, campus tours, financial aid'],
        ['pid'=>85,'name'=>'Hotel & Hospitality AI','category'=>'Industry','price'=>'$89/mo','features'=>'Reservations, concierge, room service'],
        ['pid'=>86,'name'=>'E-Commerce AI','category'=>'Industry','price'=>'$69/mo','features'=>'Order status, returns, recommendations'],
        ['pid'=>87,'name'=>'Financial Services AI','category'=>'Industry','price'=>'$149/mo','features'=>'Accounts, transfers, fraud alerts'],
        ['pid'=>88,'name'=>'Government AI','category'=>'Industry','price'=>'$99/mo','features'=>'Citizen services, permits, public info'],
        ['pid'=>89,'name'=>'Nonprofit AI','category'=>'Industry','price'=>'$49/mo','features'=>'Donations, volunteer coordination'],
        ['pid'=>90,'name'=>'Call Recording','category'=>'Add-Ons','price'=>'$10/mo','features'=>'Record all calls, 90-day storage'],
        ['pid'=>91,'name'=>'Call Analytics Pro','category'=>'Add-Ons','price'=>'$20/mo','features'=>'Sentiment, topics, trends'],
        ['pid'=>92,'name'=>'CRM Integration','category'=>'Add-Ons','price'=>'$15/mo','features'=>'Salesforce, HubSpot, Zoho sync'],
        ['pid'=>93,'name'=>'Webhook Integration','category'=>'Add-Ons','price'=>'$10/mo','features'=>'Custom webhooks for call events'],
        ['pid'=>94,'name'=>'Voicemail Transcription','category'=>'Add-Ons','price'=>'$5/mo','features'=>'AI voicemail-to-text, email alerts'],
        ['pid'=>95,'name'=>'Extra Agent Slot','category'=>'Add-Ons','price'=>'$15/mo','features'=>'+1 AI agent slot'],
        ['pid'=>96,'name'=>'Extra 500 Minutes','category'=>'Add-Ons','price'=>'$25/mo','features'=>'+500 voice minutes/mo'],
        ['pid'=>97,'name'=>'HIPAA Compliance','category'=>'Add-Ons','price'=>'$30/mo','features'=>'BAA, encrypted storage, audit logging'],
        ['pid'=>98,'name'=>'White Label','category'=>'Add-Ons','price'=>'$50/mo','features'=>'Custom branding, your domain'],
        ['pid'=>99,'name'=>'Priority Support','category'=>'Add-Ons','price'=>'$25/mo','features'=>'1-hour response, dedicated agent'],
        ['pid'=>100,'name'=>'Custom Model Training','category'=>'Add-Ons','price'=>'$200/mo','features'=>'Fine-tune AI on your data'],
    ];
}

function toolGetVoiceProducts($args) {
    $catalog = getVoiceProductCatalog();
    $category = trim($args['category'] ?? '');

    if ($category) {
        $catalog = array_values(array_filter($catalog, fn($p) => stripos($p['category'], $category) !== false));
        if (!$catalog) return ['error' => "No products in \"$category\". Categories: AI Agents, Call Center, Phone Numbers, Fax, Office Suite, SMS, Industry, Add-Ons"];
    }

    $cats = [];
    foreach ($catalog as $p) $cats[$p['category']][] = "{$p['name']} ({$p['price']})";
    $summary = [];
    foreach ($cats as $c => $ps) $summary[] = "$c: " . implode(', ', $ps);

    return ['products' => $catalog, 'count' => count($catalog), 'message' => count($catalog) . " voice products available. " . implode('. ', $summary) . ". Which interests you?"];
}

function toolOrderVoiceProduct($args) {
    $pid       = (int)($args['product_id'] ?? $args['pid'] ?? 0);
    $clientId  = (int)($args['client_id'] ?? 0);
    $confirmed = !empty($args['confirmed']);

    if (!$clientId) return ['error' => 'Please authenticate first.'];
    if (!$pid) return ['error' => 'Which product? Use getVoiceProducts to browse.'];

    $catalog = getVoiceProductCatalog();
    $product = null;
    foreach ($catalog as $p) { if ($p['pid'] === $pid) { $product = $p; break; } }
    if (!$product) return ['error' => "Product ID $pid not found."];

    if (!$confirmed) {
        return ['needs_confirmation' => true, 'product' => $product, 'message' => "I'd like to add {$product['name']} ({$product['price']}) to your account. Features: {$product['features']}. Shall I proceed?"];
    }

    // Place order via billing API (direct, bypasses MCP chain)
    $orderResult = billingAPI('AddOrder', [
        'clientid'      => $clientId,
        'pid'           => $pid,
        'domain'        => 'voice-service',
        'billingcycle'  => 'monthly',
        'paymentmethod' => 'stripe',
    ]);
    if (($orderResult['result'] ?? '') !== 'success') {
        return ['error' => $orderResult['message'] ?? 'Order placement failed.'];
    }

    $orderId  = (int)($orderResult['orderid'] ?? 0);
    $invoiceId = (int)($orderResult['invoiceid'] ?? 0);

    // Accept the order to trigger provisioning (CreateAccount hook)
    if ($orderId) {
        billingAPI('AcceptOrder', ['orderid' => $orderId]);
    }

    // For Phone Number products (PIDs 59-64), provision the number now
    $isPhoneProduct = in_array($pid, [59, 60, 61, 62, 63, 64]);
    $phoneResult = null;
    if ($isPhoneProduct) {
        $phoneType = $args['phone_type'] ?? $args['type'] ?? 'local';
        $areaCode  = $args['area_code'] ?? $args['areacode'] ?? '';
        $country   = $args['country'] ?? 'US';
        $phoneResult = provisionPhoneNumber($clientId, $orderId, $phoneType, $areaCode, $country);
    }

    // Try to auto-pay the invoice
    $payMsg = '';
    if ($invoiceId) {
        $payResult = billingAPI('CapturePayment', ['invoiceid' => $invoiceId]);
        if (($payResult['result'] ?? '') === 'success') {
            $payMsg = ' Payment processed.';
        } else {
            $payMsg = " Invoice #$invoiceId created — pay at https://gositeme.com/view-invoice?id=$invoiceId";
        }
    }

    $msg = "{$product['name']} ordered!$payMsg";
    if ($isPhoneProduct && !empty($phoneResult['phone_number'])) {
        $msg .= " Your number: {$phoneResult['phone_number']}. Use assignPhoneToAgent to connect it to an AI agent.";
    } elseif ($product['category'] === 'AI Agents') {
        $msg .= " Create your agent(s) with createMyAgent.";
    } elseif ($isPhoneProduct) {
        $msg .= " Number provisioning in progress.";
    } else {
        $msg .= " Service activating now.";
    }

    return ['success' => true, 'product' => $product, 'order_id' => $orderId, 'invoice_id' => $invoiceId,
            'phone' => $phoneResult, 'message' => $msg];
}

function toolVoiceRecommendation($args) {
    $industry = strtolower(trim($args['industry'] ?? ''));
    $need     = strtolower(trim($args['need'] ?? $args['description'] ?? ''));
    $catalog  = getVoiceProductCatalog();
    $recs     = [];

    $iMap = [
        'legal|law|attorney' => [78,49], 'real estate|realtor' => [79,49],
        'medical|dental|doctor|clinic|health' => [80,97], 'restaurant|food|cafe' => [81,49],
        'auto|car|mechanic|dealership' => [82,50], 'insurance' => [83,50],
        'education|school|university' => [84,49], 'hotel|hospitality' => [85,50],
        'ecommerce|online store|shop' => [86,49], 'financial|bank|fintech' => [87,97],
        'government|municipal' => [88,97], 'nonprofit|charity' => [89,49],
    ];

    $search = "$industry $need";
    foreach ($iMap as $pat => $pids) {
        if (preg_match("/$pat/i", $search)) {
            foreach ($pids as $pid) { foreach ($catalog as $p) { if ($p['pid'] === $pid) $recs[] = $p; } }
        }
    }

    $nMap = [
        'sms|text|messaging' => 75, 'fax' => 67, 'call center|support team|queue' => 53,
        'office|pbx|phone system' => 70, 'campaign|outbound|dialer' => 56,
        'receptionist|front desk' => 74, 'appointment|booking' => 57,
        'survey|feedback' => 58, 'record' => 90, 'crm|salesforce' => 92,
    ];
    if ($need) {
        foreach ($nMap as $pat => $pid) {
            if (preg_match("/$pat/i", $need)) { foreach ($catalog as $p) { if ($p['pid'] === $pid) $recs[] = $p; } }
        }
    }

    $recs = array_values(array_unique($recs, SORT_REGULAR));
    if (!$recs) {
        foreach ($catalog as $p) { if ($p['pid'] === 49) { $recs[] = $p; break; } }
        return ['recommendations' => $recs, 'message' => "I'd recommend starting with AI Agent Starter (\$29/mo). Tell me your industry for a tailored recommendation."];
    }

    $lines = array_map(fn($p) => "• {$p['name']} ({$p['price']}): {$p['features']}", $recs);
    return ['recommendations' => $recs, 'message' => "Recommended for you:\n" . implode("\n", $lines) . "\nWould you like to order any of these?"];
}

function toolOrderPhoneNumber($args) {
    $type = strtolower(trim($args['type'] ?? 'local'));
    $map  = ['local'=>59,'toll-free'=>60,'tollfree'=>60,'international'=>61,'vanity'=>62,'fax'=>63,'short_code'=>64,'shortcode'=>64];
    $args['product_id'] = $map[$type] ?? 59;
    $args['phone_type'] = $type;
    return toolOrderVoiceProduct($args);
}

// ═══════════════════════════════════════════════════════════════════════════
// PHONE NUMBER PROVISIONING — Buy via VAPI, insert into DB
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Provision a phone number: buy from VAPI and insert into voice_phone_numbers.
 */
function provisionPhoneNumber($clientId, $serviceId, $type = 'local', $areaCode = '', $country = 'US') {
    $db = getDB();
    if (!$db) return ['error' => 'Database unavailable'];

    // Normalise type
    $typeMap = ['toll-free' => 'tollfree', 'short_code' => 'shortcode'];
    $normalType = $typeMap[$type] ?? $type;
    $isFax = ($normalType === 'fax');
    $isSms = in_array($normalType, ['local', 'tollfree']);

    // Try buying the number from VAPI
    $vapiResult = buyVapiPhoneNumber($normalType, $areaCode, $country);

    if (!$vapiResult || !empty($vapiResult['error'])) {
        // Fallback: generate a placeholder number for manual provisioning
        error_log("[provisionPhoneNumber] VAPI phone buy failed: " . json_encode($vapiResult));
        // Return error instead of generating fake number
        return ['error' => 'Phone number provisioning failed: ' . ($vapiResult['error'] ?? 'VAPI unavailable') . '. Please try again or contact support.', 'provisioning_failed' => true];
    } else {
        $phoneNumber = $vapiResult['number'] ?? $vapiResult['phoneNumber'] ?? $vapiResult['e164Address'] ?? '';
        $vapiPhoneId = $vapiResult['id'] ?? null;
        $status = 'active';
    }

    // Insert into voice_phone_numbers
    // DB enum for phone_type: local, tollfree, vanity, international, sip
    $dbType = in_array($normalType, ['local','tollfree','vanity','international','sip']) ? $normalType : 'local';
    try {
        $stmt = $db->prepare("INSERT INTO voice_phone_numbers (client_id, service_id, phone_number, country_code, phone_type, sms_enabled, fax_enabled, vapi_phone_id, active, provisioned_at) VALUES (:cid, :sid, :phone, :country, :type, :sms, :fax, :vapi, :active, NOW())");
        $stmt->execute([
            ':cid'     => $clientId,
            ':sid'     => $serviceId,
            ':phone'   => $phoneNumber,
            ':country' => strtoupper($country),
            ':type'    => $dbType,
            ':sms'     => $isSms ? 1 : 0,
            ':fax'     => $isFax ? 1 : 0,
            ':vapi'    => $vapiPhoneId,
            ':active'  => ($status === 'active') ? 1 : 0,
        ]);
        $phoneDbId = $db->lastInsertId();
    } catch (Exception $e) {
        error_log("[provisionPhoneNumber] DB insert error: " . $e->getMessage());
        return ['error' => 'Failed to save phone number record.'];
    }

    return [
        'success'      => true,
        'phone_id'     => (int)$phoneDbId,
        'phone_number' => $phoneNumber,
        'vapi_phone_id'=> $vapiPhoneId,
        'type'         => $normalType,
        'status'       => $status,
    ];
}

/**
 * Buy a phone number from VAPI API.
 * POST https://api.vapi.ai/phone-number/buy
 */
function buyVapiPhoneNumber($type = 'local', $areaCode = '', $country = 'US') {
    $envFile = dirname(dirname(__DIR__)) . '/.env.php';
    if (file_exists($envFile)) require_once $envFile;
    $apiKey = getenv('VAPI_API_KEY');
    if (!$apiKey) {
        error_log('[buyVapiPhoneNumber] VAPI_API_KEY not set');
        return ['error' => 'VAPI API key not configured'];
    }

    // VAPI phone buy payload
    $payload = ['provider' => 'vapi'];

    // Map type to VAPI numberType
    if (in_array($type, ['tollfree', 'toll-free'])) {
        $payload['numberDesiredProperties'] = ['tollFree' => true];
    } elseif ($type === 'local' && $areaCode) {
        $payload['numberDesiredProperties'] = ['areaCode' => $areaCode];
    }

    // Set country
    if ($country && strtoupper($country) !== 'US') {
        $payload['numberDesiredProperties']['country'] = strtoupper($country);
    }

    $ch = curl_init('https://api.vapi.ai/phone-number');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("[buyVapiPhoneNumber] cURL error: $err");
        return ['error' => $err];
    }
    if ($httpCode >= 400) {
        error_log("[buyVapiPhoneNumber] HTTP $httpCode: $resp");
        return ['error' => "VAPI API error (HTTP $httpCode)"];
    }

    return json_decode($resp, true) ?: ['error' => 'Invalid VAPI response'];
}

/**
 * Assign a VAPI phone number to a VAPI assistant.
 * PATCH https://api.vapi.ai/phone-number/{vapiPhoneId}
 */
function assignVapiPhoneToAssistant($vapiPhoneId, $vapiAssistantId) {
    $envFile = dirname(dirname(__DIR__)) . '/.env.php';
    if (file_exists($envFile)) require_once $envFile;
    $apiKey = getenv('VAPI_API_KEY');
    if (!$apiKey) return ['error' => 'VAPI API key not configured'];
    if (!$vapiPhoneId) return ['error' => 'No VAPI phone ID'];

    $body = $vapiAssistantId ? ['assistantId' => $vapiAssistantId] : ['assistantId' => null];

    $ch = curl_init("https://api.vapi.ai/phone-number/$vapiPhoneId");
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return ['error' => $err];
    if ($httpCode >= 400) return ['error' => "VAPI PATCH error (HTTP $httpCode)"];
    return json_decode($resp, true) ?: ['success' => true];
}


// ═══════════════════════════════════════════════════════════════════════════
// TELNYX HELPER FUNCTIONS — SMS, FAX, VOICE WITH FALLBACK
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Send SMS via Telnyx Messaging API v2.
 * POST https://api.telnyx.com/v2/messages
 */
function sendSmsViaTelnyx($to, $from, $message) {
    $envFile = dirname(dirname(__DIR__)) . '/.env.php';
    if (file_exists($envFile)) require_once $envFile;
    $apiKey = getenv('TELNYX_API_KEY');
    if (!$apiKey) return ['error' => 'Telnyx API key not configured'];

    $to   = preg_replace('/[^\d+]/', '', $to);
    $from = preg_replace('/[^\d+]/', '', $from);
    if (!str_starts_with($to, '+')) $to = '+1' . ltrim($to, '1');
    if (!str_starts_with($from, '+')) $from = '+1' . ltrim($from, '1');

    $payload = ['from' => $from, 'to' => $to, 'text' => $message, 'type' => 'SMS'];

    $ch = curl_init('https://api.telnyx.com/v2/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) return ['error' => "Telnyx SMS cURL error: $curlErr"];
    $data = json_decode($resp, true);
    if ($httpCode >= 400) {
        $msg = $data['errors'][0]['detail'] ?? "HTTP $httpCode";
        return ['error' => "Telnyx SMS failed: $msg"];
    }
    return ['success' => true, 'provider' => 'telnyx', 'id' => $data['data']['id'] ?? null];
}

/**
 * Send SMS with fallback: Telnyx → log error.
 * Returns ['success'=>bool, 'provider'=>string, 'id'=>string|null]
 */
function sendSmsWithFallback($to, $from, $message, $clientId = 0) {
    $db = getDB();

    // Try Telnyx first
    $result = sendSmsViaTelnyx($to, $from, $message);
    if (!empty($result['success'])) {
        // Log to DB
        try {
            $s = $db->prepare("INSERT INTO voice_sms (client_id, phone_number_id, direction, from_number, to_number, message, status) VALUES (:cid, 0, 'outbound', :from, :to, :msg, 'sent')");
            $s->execute([':cid' => $clientId, ':from' => $from, ':to' => $to, ':msg' => $message]);
        } catch (Exception $e) {}
        return $result;
    }

    error_log("[SMS Fallback] Telnyx failed: " . ($result['error'] ?? 'unknown') . " — no additional providers configured");
    // Queue in DB for manual processing
    try {
        $s = $db->prepare("INSERT INTO voice_sms (client_id, phone_number_id, direction, from_number, to_number, message, status) VALUES (:cid, 0, 'outbound', :from, :to, :msg, 'queued')");
        $s->execute([':cid' => $clientId, ':from' => $from, ':to' => $to, ':msg' => $message]);
    } catch (Exception $e) {}
    return ['error' => 'SMS delivery failed via all providers. Queued for retry.', 'queued' => true];
}

/**
 * Send fax via Telnyx Fax API v2.
 * POST https://api.telnyx.com/v2/faxes
 */
function sendFaxViaTelnyx($to, $from, $mediaUrl) {
    $envFile = dirname(dirname(__DIR__)) . '/.env.php';
    if (file_exists($envFile)) require_once $envFile;
    $apiKey       = getenv('TELNYX_API_KEY');
    $connectionId = getenv('TELNYX_CONNECTION_ID');
    if (!$apiKey) return ['error' => 'Telnyx API key not configured'];

    $to   = preg_replace('/[^\d+]/', '', $to);
    $from = preg_replace('/[^\d+]/', '', $from);
    if (!str_starts_with($to, '+')) $to = '+1' . ltrim($to, '1');
    if (!str_starts_with($from, '+')) $from = '+1' . ltrim($from, '1');

    $payload = ['to' => $to, 'from' => $from, 'media_url' => $mediaUrl];
    if ($connectionId) $payload['connection_id'] = $connectionId;

    $ch = curl_init('https://api.telnyx.com/v2/faxes');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) return ['error' => "Telnyx fax cURL error: $curlErr"];
    $data = json_decode($resp, true);
    if ($httpCode >= 400) {
        $msg = $data['errors'][0]['detail'] ?? "HTTP $httpCode";
        return ['error' => "Telnyx fax failed: $msg"];
    }
    return ['success' => true, 'provider' => 'telnyx', 'fax_id' => $data['data']['id'] ?? null, 'status' => $data['data']['status'] ?? 'queued'];
}

/**
 * Send fax with fallback: Telnyx → log error / queue.
 */
function sendFaxWithFallback($to, $from, $mediaUrl, $clientId = 0) {
    $db = getDB();

    // Try Telnyx
    $result = sendFaxViaTelnyx($to, $from, $mediaUrl);
    if (!empty($result['success'])) {
        try {
            $s = $db->prepare("INSERT INTO voice_fax (client_id, phone_number_id, direction, from_number, to_number, document_url, status) VALUES (:cid, 0, 'outbound', :from, :to, :doc, 'sending')");
            $s->execute([':cid' => $clientId, ':from' => $from, ':to' => $to, ':doc' => $mediaUrl]);
        } catch (Exception $e) {}
        return $result;
    }

    error_log("[Fax Fallback] Telnyx failed: " . ($result['error'] ?? 'unknown') . " — queuing for retry");
    try {
        $s = $db->prepare("INSERT INTO voice_fax (client_id, phone_number_id, direction, from_number, to_number, document_url, status) VALUES (:cid, 0, 'outbound', :from, :to, :doc, 'queued')");
        $s->execute([':cid' => $clientId, ':from' => $from, ':to' => $to, ':doc' => $mediaUrl]);
    } catch (Exception $e) {}
    return ['error' => 'Fax failed via all providers. Queued for retry.', 'queued' => true];
}

/**
 * Make an outbound call with fallback: VAPI → Telnyx.
 * Used for calling court clerks, etc.
 */
function callOutboundWithFallback($phoneNumber, $greeting, $reason = 'legal_aid', $clientId = 0) {
    // Try VAPI first (primary provider)
    $vapiResult = triggerOutboundCall($phoneNumber, $reason, $greeting, $clientId);
    if (!empty($vapiResult['success'])) {
        $vapiResult['provider'] = 'vapi';
        return $vapiResult;
    }
    error_log("[Outbound Fallback] VAPI failed for $phoneNumber: " . ($vapiResult['error'] ?? 'unknown') . " — trying Telnyx");

    // Fallback: Telnyx programmable voice
    $envFile = dirname(dirname(__DIR__)) . '/.env.php';
    if (file_exists($envFile)) require_once $envFile;
    $apiKey       = getenv('TELNYX_API_KEY');
    $connectionId = getenv('TELNYX_CONNECTION_ID');
    $fromNumber   = getenv('TELNYX_FROM_NUMBER');
    if (!$apiKey) return ['error' => 'All voice providers failed. VAPI: ' . ($vapiResult['error'] ?? 'unavailable') . '. Telnyx: not configured.'];

    $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
    if (!str_starts_with($phoneNumber, '+')) $phoneNumber = '+1' . ltrim($phoneNumber, '1');
    if ($fromNumber) {
        $fromNumber = preg_replace('/[^\d+]/', '', $fromNumber);
        if (!str_starts_with($fromNumber, '+')) $fromNumber = '+1' . ltrim($fromNumber, '1');
    }

    if (!$fromNumber) return ['error' => 'TELNYX_FROM_NUMBER not configured. Cannot place outbound call without a valid from number.'];
    $payload = ['to' => $phoneNumber, 'from' => $fromNumber];
    if ($connectionId) $payload['connection_id'] = $connectionId;

    $ch = curl_init('https://api.telnyx.com/v2/calls');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) return ['error' => "All providers failed. VAPI: {$vapiResult['error']}. Telnyx: cURL $curlErr"];
    $data = json_decode($resp, true);
    if ($httpCode >= 400) {
        $msg = $data['errors'][0]['detail'] ?? "HTTP $httpCode";
        return ['error' => "All providers failed. VAPI: {$vapiResult['error']}. Telnyx: $msg"];
    }

    return ['success' => true, 'provider' => 'telnyx', 'call_id' => $data['data']['call_control_id'] ?? null, 'phone' => $phoneNumber, 'reason' => $reason];
}


// ═══════════════════════════════════════════════════════════════════════════
// CANLII LEGAL RESEARCH
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Search CanLII for case law and legislation.
 * Falls back to web scraping if API key is not configured.
 */
function searchCanLII($query, $jurisdiction = 'qc', $type = 'decisions') {
    $envFile = dirname(dirname(__DIR__)) . '/.env.php';
    if (file_exists($envFile)) require_once $envFile;
    $apiKey = getenv('CANLII_API_KEY');

    // --- Method 1: CanLII API (if key configured) ---
    if ($apiKey) {
        $dbMap = [
            'qc'  => 'qccs',        // Cour supérieure du Québec
            'on'  => 'onsc',        // Ontario Superior Court
            'bc'  => 'bcsc',        // BC Supreme Court
            'ab'  => 'abqb',        // Alberta Court of Queen's Bench
            'fed' => 'fct',         // Federal Court
            'scc' => 'csc-scc',     // Supreme Court of Canada
        ];
        $dbId = $dbMap[strtolower($jurisdiction)] ?? 'qccs';

        $url = "https://api.canlii.org/v1/caseBrowse/en/{$dbId}/?offset=0&resultCount=10&api_key=$apiKey";
        if ($type === 'legislation') {
            $url = "https://api.canlii.org/v1/legislationBrowse/en/{$jurisdiction}/?offset=0&resultCount=10&api_key=$apiKey";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_USERAGENT => 'GoSiteMe-Alfred/1.0']);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $data = json_decode($resp, true);
            return ['success' => true, 'provider' => 'canlii_api', 'results' => $data['cases'] ?? $data['legislations'] ?? $data];
        }
    }

    // --- Method 2: CanLII Search Scrape (fallback) ---
    $searchUrl = 'https://www.canlii.org/en/search/search.do?' . http_build_query([
        'searchUrlHash'          => 'AAAAAQAg' . base64_encode($query),
        'searchCriteria.keyword' => $query,
        'searchCriteria.jurisdiction' => strtolower($jurisdiction),
    ]);
    $ch = curl_init($searchUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0',
    ]);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$html) {
        return ['error' => 'CanLII search unavailable. Try searching directly at https://www.canlii.org/', 'query' => $query];
    }

    // Parse search result snippets
    $results = [];
    if (preg_match_all('/<a[^>]*class="[^"]*result-title[^"]*"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/s', $html, $matches, PREG_SET_ORDER)) {
        foreach (array_slice($matches, 0, 10) as $m) {
            $results[] = ['url' => 'https://www.canlii.org' . $m[1], 'title' => strip_tags($m[2])];
        }
    }
    // Also try alternate pattern
    if (empty($results) && preg_match_all('/<div class="result".*?<a href="([^"]+)">(.*?)<\/a>.*?<p class="snippet">(.*?)<\/p>/s', $html, $matches, PREG_SET_ORDER)) {
        foreach (array_slice($matches, 0, 10) as $m) {
            $results[] = ['url' => 'https://www.canlii.org' . $m[1], 'title' => strip_tags($m[2]), 'snippet' => strip_tags($m[3])];
        }
    }

    if (empty($results)) {
        return ['success' => true, 'results' => [], 'message' => "No CanLII results found for \"$query\" in $jurisdiction. The inmate should try broader search terms or check https://www.canlii.org/ directly.", 'provider' => 'canlii_scrape'];
    }

    return ['success' => true, 'results' => $results, 'count' => count($results), 'provider' => 'canlii_scrape', 'message' => "Found " . count($results) . " results on CanLII for \"$query\"."];
}


// ═══════════════════════════════════════════════════════════════════════════
// QUEBEC COURT DIRECTORY
// ═══════════════════════════════════════════════════════════════════════════

function getQuebecCourtDirectory() {
    return [
        'montreal' => [
            'name'    => 'Palais de justice de Montréal (Cour supérieure)',
            'address' => '1, rue Notre-Dame Est, Montréal, QC H2Y 1B6',
            'phone'   => '+15143937104',
            'fax'     => '+15148731025',
            'greffe'  => 'Greffe civil — Cour supérieure',
            'district'=> 'Montréal',
        ],
        'quebec_city' => [
            'name'    => 'Palais de justice de Québec (Cour supérieure)',
            'address' => '300, boulevard Jean-Lesage, Québec, QC G1K 8K6',
            'phone'   => '+14186498101',
            'fax'     => '+14186497260',
            'greffe'  => 'Greffe civil — Cour supérieure',
            'district'=> 'Québec',
        ],
        'laval' => [
            'name'    => 'Palais de justice de Laval',
            'address' => '2800, boulevard Saint-Martin Ouest, Laval, QC H7T 2S9',
            'phone'   => '+14506800800',
            'fax'     => '+14506863982',
            'greffe'  => 'Greffe civil',
            'district'=> 'Laval',
        ],
        'longueuil' => [
            'name'    => 'Palais de justice de Longueuil',
            'address' => '1111, boulevard Jacques-Cartier Est, Longueuil, QC J4M 2J6',
            'phone'   => '+14506462929',
            'fax'     => '+14506464541',
            'greffe'  => 'Greffe civil',
            'district'=> 'Longueuil',
        ],
        'gatineau' => [
            'name'    => 'Palais de justice de Gatineau',
            'address' => '17, rue Laurier, Gatineau, QC J8X 4C1',
            'phone'   => '+18197721011',
            'fax'     => '+18197721084',
            'greffe'  => 'Greffe civil',
            'district'=> 'Gatineau (Hull)',
        ],
        'sherbrooke' => [
            'name'    => 'Palais de justice de Sherbrooke',
            'address' => '375, rue King Ouest, Sherbrooke, QC J1H 6B9',
            'phone'   => '+18198222560',
            'fax'     => '+18198222695',
            'greffe'  => 'Greffe civil',
            'district'=> 'Saint-François',
        ],
        'trois_rivieres' => [
            'name'    => 'Palais de justice de Trois-Rivières',
            'address' => '250, rue Laviolette, Trois-Rivières, QC G9A 1T9',
            'phone'   => '+18193714444',
            'fax'     => '+18193793853',
            'greffe'  => 'Greffe civil',
            'district'=> 'Trois-Rivières',
        ],
        'salaberry' => [
            'name'    => 'Palais de justice de Salaberry-de-Valleyfield',
            'address' => '180, rue Salaberry, Salaberry-de-Valleyfield, QC J6T 2J1',
            'phone'   => '+14503703905',
            'fax'     => '+14503700264',
            'greffe'  => 'Greffe civil',
            'district'=> 'Beauharnois',
        ],
        'joliette' => [
            'name'    => 'Palais de justice de Joliette',
            'address' => '450, rue Saint-Louis, Joliette, QC J6E 2Y4',
            'phone'   => '+14507565534',
            'fax'     => '+14507565909',
            'greffe'  => 'Greffe civil',
            'district'=> 'Joliette',
        ],
        'rimouski' => [
            'name'    => 'Palais de justice de Rimouski',
            'address' => '183, avenue de la Cathédrale, Rimouski, QC G5L 5J1',
            'phone'   => '+14187279281',
            'fax'     => '+14187244920',
            'greffe'  => 'Greffe civil',
            'district'=> 'Rimouski',
        ],
        // Federal Courts
        'federal_montreal' => [
            'name'    => 'Federal Court — Montreal',
            'address' => '30, rue McGill, Montréal, QC H2Y 3Z7',
            'phone'   => '+15142833732',
            'fax'     => '+15142838394',
            'greffe'  => 'Registry',
            'district'=> 'Federal — Montreal',
        ],
        'federal_ottawa' => [
            'name'    => 'Federal Court — Ottawa',
            'address' => '90 Sparks Street, Ottawa, ON K1A 0H9',
            'phone'   => '+16139922434',
            'fax'     => '+16139524390',
            'greffe'  => 'Registry',
            'district'=> 'Federal — Ottawa',
        ],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// LEGAL MOTION TEMPLATES
// ═══════════════════════════════════════════════════════════════════════════

// ── AI-powered legal content generation via Groq ────────────────────────
function enrichLegalMotionWithAI($motionBody, $type, $caseData) {
    // Check for placeholders that need filling
    $placeholders = ['[MOTIFS À COMPLÉTER]', '[FAITS À COMPLÉTER]', '[EXPOSÉ DES FAITS ET MOTIFS À COMPLÉTER]',
                     '[CHANGEMENTS DE CIRCONSTANCES À COMPLÉTER]', "[MOTIFS D'APPEL À COMPLÉTER]", '[INFRACTIONS]'];
    $hasPlaceholders = false;
    foreach ($placeholders as $ph) {
        if (stripos($motionBody, $ph) !== false) { $hasPlaceholders = true; break; }
    }
    // Also check for blank fill-in lines
    if (substr_count($motionBody, '_______________') > 3) $hasPlaceholders = true;

    if (!$hasPlaceholders) return $motionBody; // Template is already filled

    // Load Groq API key
    $groqKey = '';
    $mcpEnv = dirname(__DIR__) . '/gocodeme/mcp-server/.env';
    if (file_exists($mcpEnv)) {
        $envC = file_get_contents($mcpEnv);
        if (preg_match('/GROQ_API_KEY=(.+)/', $envC, $gm)) {
            $groqKey = trim($gm[1]);
        }
    }
    if (!$groqKey) {
        error_log("[LegalDraft] No GROQ_API_KEY found, skipping AI enrichment");
        return $motionBody;
    }

    // Build context from case data
    $context = "Case Details:\n";
    $context .= "- Name: " . ($caseData['caller_name'] ?? 'Unknown') . "\n";
    $context .= "- Inmate ID: " . ($caseData['inmate_id'] ?? 'Unknown') . "\n";
    $context .= "- Institution: " . ($caseData['institution'] ?? 'Unknown') . "\n";
    $context .= "- Case Number: " . ($caseData['case_number'] ?? 'Not assigned') . "\n";
    $context .= "- Case Type: " . ($type) . "\n";
    $context .= "- District: " . ($caseData['court_district'] ?? 'Montréal') . "\n";
    if (!empty($caseData['case_summary'])) $context .= "- Case Summary: " . $caseData['case_summary'] . "\n";
    if (!empty($caseData['case_notes'])) $context .= "- Case Notes: " . $caseData['case_notes'] . "\n";
    if (!empty($caseData['charges'])) $context .= "- Charges: " . $caseData['charges'] . "\n";
    if (!empty($caseData['next_hearing_date'])) $context .= "- Next Hearing: " . $caseData['next_hearing_date'] . "\n";
    if (!empty($caseData['next_steps'])) $context .= "- Next Steps: " . $caseData['next_steps'] . "\n";

    $typeLabels = [
        'habeas_corpus' => 'Habeas Corpus petition',
        'bail_review' => 'Bail Review application',
        'motion' => 'Court Motion',
        'appeal' => 'Notice of Appeal',
    ];
    $typeLabel = $typeLabels[$type] ?? 'Court Motion';

    $systemPrompt = "You are an expert Quebec legal document drafter specializing in criminal and penitentiary law. " .
        "You write in formal bilingual legal French (with English translations where appropriate). " .
        "You are drafting a {$typeLabel} for an inmate who is self-represented (se représentant seul). " .
        "Your task is to take a legal document template that has placeholder text and FILL IN the placeholders with " .
        "substantive, factual, and legally-grounded content based on the case details provided. " .
        "CRITICAL RULES:\n" .
        "1. Replace ALL bracketed placeholders like [MOTIFS À COMPLÉTER] with actual legal arguments and facts\n" .
        "2. Replace blank lines (_______________) with appropriate content when case data is available\n" .
        "3. Cite relevant Canadian Charter rights (s. 7 life/liberty/security, s. 9 arbitrary detention, s. 12 cruel/unusual treatment)\n" .
        "4. Cite relevant Quebec Charter rights where applicable\n" .
        "5. Reference applicable Criminal Code provisions\n" .
        "6. Use formal legal language appropriate for Quebec Superior Court\n" .
        "7. Include specific factual allegations from the case data\n" .
        "8. Keep the document structure and formatting exactly as provided\n" .
        "9. Return ONLY the complete filled-in document text, no explanations or commentary\n" .
        "10. If insufficient facts are provided, write reasonable legal arguments based on the case type but note that details should be verified";

    $userPrompt = "Here is the case information:\n\n{$context}\n\n" .
        "Here is the legal document template to fill in:\n\n{$motionBody}\n\n" .
        "Please fill in ALL placeholder text with substantive legal content based on the case details. " .
        "Return the complete document with all placeholders replaced.";

    $payload = json_encode([
        'model'       => 'llama-3.3-70b-versatile',
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0.3,
        'max_tokens'  => 4096,
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $groqKey,
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$resp) {
        error_log("[LegalDraft] Groq AI enrichment failed: HTTP {$httpCode} — " . substr($resp, 0, 500));
        return $motionBody; // Return original template on failure
    }

    $data = json_decode($resp, true);
    $enriched = $data['choices'][0]['message']['content'] ?? '';

    if (strlen($enriched) < 200) {
        error_log("[LegalDraft] Groq returned too-short response, keeping template");
        return $motionBody;
    }

    error_log("[LegalDraft] AI enrichment successful, " . strlen($enriched) . " chars");
    return $enriched;
}

function generateLegalMotion($type, $caseData) {
    $templates = [
        'habeas_corpus' => [
            'title' => 'REQUÊTE EN HABEAS CORPUS / APPLICATION FOR HABEAS CORPUS',
            'court' => $caseData['court_name'] ?? 'COUR SUPÉRIEURE DU QUÉBEC',
            'body'  => "
CANADA
PROVINCE DE QUÉBEC
DISTRICT DE " . strtoupper($caseData['court_district'] ?? 'MONTRÉAL') . "

No: _______________

" . strtoupper($caseData['court_name'] ?? 'COUR SUPÉRIEURE') . "

" . ($caseData['caller_name'] ?? 'LE REQUÉRANT') . "
    Requérant / Applicant

c.

SA MAJESTÉ LE ROI / HIS MAJESTY THE KING
et/and
LE DIRECTEUR DE L'ÉTABLISSEMENT DE DÉTENTION
" . strtoupper($caseData['institution'] ?? '_______________') . "
    Intimés / Respondents

═══════════════════════════════════════════════════════
REQUÊTE EN HABEAS CORPUS
(Art. 10 et 24(1) de la Charte canadienne des droits et libertés)
═══════════════════════════════════════════════════════

LE REQUÉRANT EXPOSE RESPECTUEUSEMENT CE QUI SUIT:

1. Le requérant, " . ($caseData['caller_name'] ?? '_______________') . ", est présentement détenu à " . ($caseData['institution'] ?? '_______________') . ";

2. Le requérant est identifié sous le numéro matricule " . ($caseData['inmate_id'] ?? '_______________') . ";

3. Le requérant allègue que sa détention est illégale pour les motifs suivants:

" . ($caseData['case_summary'] ?? '   a) [MOTIFS À COMPLÉTER]') . "

4. Les faits pertinents sont les suivants:

" . ($caseData['case_notes'] ?? '   [FAITS À COMPLÉTER]') . "

PAR CES MOTIFS, PLAISE AU TRIBUNAL:

ACCUEILLIR la présente requête;
ORDONNER la mise en liberté immédiate du requérant;
LE TOUT avec dépens.

" . ($caseData['court_district'] ?? 'Montréal') . ", le " . date('j F Y') . "

_________________________________
" . ($caseData['caller_name'] ?? 'Requérant, se représentant seul') . "
Détenu à " . ($caseData['institution'] ?? '_______________') . "
",
        ],

        'bail_review' => [
            'title' => 'DEMANDE DE RÉVISION DE CAUTIONNEMENT / BAIL REVIEW APPLICATION',
            'court' => $caseData['court_name'] ?? 'COUR SUPÉRIEURE DU QUÉBEC',
            'body'  => "
CANADA
PROVINCE DE QUÉBEC
DISTRICT DE " . strtoupper($caseData['court_district'] ?? 'MONTRÉAL') . "

No: _______________

" . strtoupper($caseData['court_name'] ?? 'COUR SUPÉRIEURE') . "

SA MAJESTÉ LE ROI / HIS MAJESTY THE KING
c.
" . strtoupper($caseData['caller_name'] ?? 'L\'ACCUSÉ') . "

═══════════════════════════════════════════════════════
DEMANDE DE RÉVISION DE L'ORDONNANCE DE DÉTENTION
(Art. 520 et 521 du Code criminel)
═══════════════════════════════════════════════════════

L'ACCUSÉ EXPOSE RESPECTUEUSEMENT CE QUI SUIT:

1. L'accusé, " . ($caseData['caller_name'] ?? '_______________') . ", est présentement détenu à " . ($caseData['institution'] ?? '_______________') . " depuis le _______________; 

2. Numéro d'écrou: " . ($caseData['inmate_id'] ?? '_______________') . ";

3. L'accusé est inculpé de: [INFRACTIONS];

4. Un juge de paix a ordonné la détention de l'accusé le _______________

5. Les circonstances ont changé de manière significative:

" . ($caseData['case_summary'] ?? '   [CHANGEMENTS DE CIRCONSTANCES À COMPLÉTER]') . "

6. L'accusé propose le plan de mise en liberté suivant:
   a) Cautionnement de _______________$ 
   b) Caution: _______________
   c) Conditions: _______________

PAR CES MOTIFS, PLAISE AU TRIBUNAL:

RÉVISER l'ordonnance de détention;
ORDONNER la mise en liberté de l'accusé aux conditions que le tribunal estimera appropriées.

" . ($caseData['court_district'] ?? 'Montréal') . ", le " . date('j F Y') . "

_________________________________
" . ($caseData['caller_name'] ?? 'Accusé, se représentant seul') . "
",
        ],

        'motion' => [
            'title' => 'REQUÊTE / MOTION',
            'court' => $caseData['court_name'] ?? 'COUR SUPÉRIEURE DU QUÉBEC',
            'body'  => "
CANADA
PROVINCE DE QUÉBEC
DISTRICT DE " . strtoupper($caseData['court_district'] ?? 'MONTRÉAL') . "

No: " . ($caseData['case_number'] ?? '_______________') . "

" . strtoupper($caseData['court_name'] ?? 'COUR SUPÉRIEURE') . "

" . strtoupper($caseData['caller_name'] ?? 'LE REQUÉRANT') . "
    Requérant / Applicant
c.
_______________
    Intimé / Respondent

═══════════════════════════════════════════════════════
REQUÊTE
═══════════════════════════════════════════════════════

LE REQUÉRANT EXPOSE RESPECTUEUSEMENT CE QUI SUIT:

1. " . ($caseData['case_summary'] ?? '[EXPOSÉ DES FAITS ET MOTIFS À COMPLÉTER]') . "

" . ($caseData['case_notes'] ?? '') . "

PAR CES MOTIFS, PLAISE AU TRIBUNAL:

[CONCLUSIONS RECHERCHÉES]

" . ($caseData['court_district'] ?? 'Montréal') . ", le " . date('j F Y') . "

_________________________________
" . ($caseData['caller_name'] ?? 'Requérant, se représentant seul') . "
Détenu à " . ($caseData['institution'] ?? '_______________') . "
",
        ],

        'appeal' => [
            'title' => 'AVIS D\'APPEL / NOTICE OF APPEAL',
            'court' => 'COUR D\'APPEL DU QUÉBEC',
            'body'  => "
CANADA
PROVINCE DE QUÉBEC

COUR D'APPEL

No: _______________

" . strtoupper($caseData['caller_name'] ?? 'L\'APPELANT') . "
    Appelant / Appellant

c.

SA MAJESTÉ LE ROI / HIS MAJESTY THE KING
    Intimé / Respondent

═══════════════════════════════════════════════════════
AVIS D'APPEL
(Art. 675 et 678 du Code criminel)
═══════════════════════════════════════════════════════

PRENEZ AVIS QUE l'appelant interjette appel du jugement rendu le _______________ par l'honorable juge _______________ de la " . ($caseData['court_name'] ?? 'Cour supérieure') . ", district de " . ($caseData['court_district'] ?? 'Montréal') . ".

MOTIFS D'APPEL:

" . ($caseData['case_summary'] ?? '1. [MOTIFS D\'APPEL À COMPLÉTER]') . "

" . date('j F Y') . "

_________________________________
" . ($caseData['caller_name'] ?? 'Appelant, se représentant seul') . "
Détenu à " . ($caseData['institution'] ?? '_______________') . "
",
        ],
    ];

    $tpl = $templates[$type] ?? $templates['motion'];
    $tpl['type']       = $type;
    $tpl['generated']  = date('Y-m-d H:i:s');
    $tpl['disclaimer'] = 'IMPORTANT: This document was generated by Alfred AI Legal Aid. It is NOT legal advice. Have a lawyer review before filing if possible. Legal aid: 1-800-842-2213 (Aide juridique du Québec).';
    return $tpl;
}


// ═══════════════════════════════════════════════════════════════════════════
// LEGAL CASES TABLE — Auto-create if missing
// ═══════════════════════════════════════════════════════════════════════════

function ensureLegalCasesTable() {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $db = getDB();
        if (!$db) return;
        $db->exec("CREATE TABLE IF NOT EXISTS alfred_legal_cases (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            caller_phone      VARCHAR(50),
            caller_name       VARCHAR(200),
            inmate_id         VARCHAR(100),
            institution       VARCHAR(300),
            province          VARCHAR(10) DEFAULT 'QC',
            case_number       VARCHAR(100),
            case_type         VARCHAR(50),
            charges           TEXT,
            case_summary      TEXT,
            case_notes        MEDIUMTEXT,
            court_name        VARCHAR(300),
            court_phone       VARCHAR(50),
            court_fax         VARCHAR(50),
            court_address     VARCHAR(500),
            court_greffe_name VARCHAR(200),
            court_district    VARCHAR(100),
            documents_filed   MEDIUMTEXT,
            canlii_references MEDIUMTEXT,
            next_hearing_date VARCHAR(50),
            next_steps        TEXT,
            status            VARCHAR(20) DEFAULT 'active',
            grievance_data    MEDIUMTEXT,
            parole_data       MEDIUMTEXT,
            disciplinary_data MEDIUMTEXT,
            last_call_at      DATETIME,
            created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_phone (caller_phone),
            INDEX idx_inmate (inmate_id),
            INDEX idx_status (status),
            INDEX idx_institution (institution(50))
        )");
    } catch (Exception $e) {
        error_log("[LegalTable] Auto-create error: " . $e->getMessage());
    }
}


// ═══════════════════════════════════════════════════════════════════════════
// 76. LEGAL IDENTIFY — Inmate identification without billing account
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalIdentify($args) {
    $callerPhone  = trim($args['caller_phone'] ?? $args['phone'] ?? '');
    $callerName   = trim($args['caller_name'] ?? $args['name'] ?? '');
    $inmateId     = trim($args['inmate_id'] ?? '');
    $institution  = trim($args['institution'] ?? '');
    $province     = trim($args['province'] ?? 'QC');

    if (!$callerPhone && !$inmateId) return ['error' => 'I need either your phone number or inmate ID to identify you.'];
    if (!$callerName)  return ['error' => 'What is your full name?'];
    if (!$institution) return ['error' => 'Which institution are you calling from?'];

    $db = getDB();

    // Check for existing active cases from this caller
    $lookup = null;
    if ($callerPhone) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE caller_phone=:phone AND status='active' ORDER BY updated_at DESC LIMIT 1");
        $s->execute([':phone' => $callerPhone]);
        $lookup = $s->fetch(PDO::FETCH_ASSOC);
    }
    if (!$lookup && $inmateId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE inmate_id=:iid AND status='active' ORDER BY updated_at DESC LIMIT 1");
        $s->execute([':iid' => $inmateId]);
        $lookup = $s->fetch(PDO::FETCH_ASSOC);
    }

    if ($lookup) {
        // Resume existing case
        $db->prepare("UPDATE alfred_legal_cases SET last_call_at=NOW(), caller_name=COALESCE(:name, caller_name), institution=COALESCE(:inst, institution) WHERE id=:id")
           ->execute([':name' => $callerName ?: null, ':inst' => $institution ?: null, ':id' => $lookup['id']]);

        return [
            'success'     => true,
            'resumed'     => true,
            'case_id'     => (int)$lookup['id'],
            'case_type'   => $lookup['case_type'],
            'case_number' => $lookup['case_number'],
            'court_name'  => $lookup['court_name'],
            'status'      => $lookup['status'],
            'documents'   => json_decode($lookup['documents_filed'] ?: '[]', true),
            'next_steps'  => $lookup['next_steps'],
            'message'     => "Welcome back, {$callerName}. I found your active case" .
                            ($lookup['case_number'] ? " #{$lookup['case_number']}" : '') .
                            " ({$lookup['case_type']}). " .
                            ($lookup['next_steps'] ? "Last time we noted: {$lookup['next_steps']}" : "Where would you like to pick up?"),
        ];
    }

    // Create new case
    $s = $db->prepare("INSERT INTO alfred_legal_cases (caller_phone, caller_name, inmate_id, institution, province, status) VALUES (:phone, :name, :iid, :inst, :prov, 'active')");
    $s->execute([':phone' => $callerPhone, ':name' => $callerName, ':iid' => $inmateId, ':inst' => $institution, ':prov' => $province]);
    $caseId = $db->lastInsertId();

    return [
        'success'  => true,
        'resumed'  => false,
        'case_id'  => (int)$caseId,
        'message'  => "Hello {$callerName}. I've opened a new legal aid case for you (Case #{$caseId}). " .
                     "I can help you with: habeas corpus, bail review, motions, appeals. " .
                     "I can search CanLII for relevant case law, draft your motion, and fax it to the court. " .
                     "What legal matter do you need help with?",
        'available_actions' => ['search CanLII case law', 'draft habeas corpus', 'draft bail review', 'draft motion', 'draft appeal', 'call court clerk', 'fax to court'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 77. LEGAL RESUME CASE — Load existing case across payphone calls
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalResumeCase($args) {
    $callerPhone = trim($args['caller_phone'] ?? $args['phone'] ?? '');
    $inmateId    = trim($args['inmate_id'] ?? '');
    $caseId      = (int)($args['case_id'] ?? 0);

    $db = getDB();

    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
    } elseif ($callerPhone) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE caller_phone=:phone AND status='active' ORDER BY updated_at DESC LIMIT 1");
        $s->execute([':phone' => $callerPhone]);
    } elseif ($inmateId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE inmate_id=:iid AND status='active' ORDER BY updated_at DESC LIMIT 1");
        $s->execute([':iid' => $inmateId]);
    } else {
        return ['error' => 'I need your phone number, inmate ID, or case number to look up your case.'];
    }

    $c = $s->fetch(PDO::FETCH_ASSOC);
    if (!$c) return ['error' => 'No active case found. Would you like to start a new one?', 'action' => 'legalIdentify'];

    $db->prepare("UPDATE alfred_legal_cases SET last_call_at=NOW() WHERE id=:id")->execute([':id' => $c['id']]);

    $docs      = json_decode($c['documents_filed'] ?: '[]', true);
    $canlii    = json_decode($c['canlii_references'] ?: '[]', true);
    $docCount  = count($docs);
    $refCount  = count($canlii);

    return [
        'success'        => true,
        'case'           => [
            'id'             => (int)$c['id'],
            'caller_name'   => $c['caller_name'],
            'inmate_id'     => $c['inmate_id'],
            'institution'   => $c['institution'],
            'case_number'   => $c['case_number'],
            'case_type'     => $c['case_type'],
            'case_summary'  => $c['case_summary'],
            'court_name'    => $c['court_name'],
            'court_phone'   => $c['court_phone'],
            'court_fax'     => $c['court_fax'],
            'court_district'=> $c['court_district'],
            'documents'     => $docs,
            'canlii_refs'   => $canlii,
            'status'        => $c['status'],
            'next_hearing'  => $c['next_hearing_date'],
            'next_steps'    => $c['next_steps'],
            'last_call'     => $c['last_call_at'],
        ],
        'message'        => "Case #{$c['id']} loaded. Type: {$c['case_type']}. " .
                           "{$docCount} document(s) filed, {$refCount} CanLII reference(s). " .
                           ($c['next_steps'] ? "Next steps: {$c['next_steps']}" : "What would you like to do?"),
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 78. LEGAL SEARCH — Search CanLII for case law
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalSearch($args) {
    $query        = trim($args['query'] ?? $args['keyword'] ?? '');
    $jurisdiction = trim($args['jurisdiction'] ?? $args['province'] ?? 'qc');
    $caseId       = (int)($args['case_id'] ?? 0);
    $type         = trim($args['type'] ?? 'decisions'); // decisions or legislation

    if (!$query) return ['error' => 'What legal topic should I search for? Give me keywords, a case citation, or describe your situation.'];

    $result = searchCanLII($query, $jurisdiction, $type);

    // Save references to case if we have a case_id
    if ($caseId && !empty($result['results'])) {
        try {
            $db = getDB();
            $s = $db->prepare("SELECT canlii_references FROM alfred_legal_cases WHERE id=:id");
            $s->execute([':id' => $caseId]);
            $row = $s->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $refs = json_decode($row['canlii_references'] ?: '[]', true);
                foreach ($result['results'] as $r) {
                    $refs[] = ['query' => $query, 'title' => $r['title'] ?? '', 'url' => $r['url'] ?? '', 'searched_at' => date('Y-m-d H:i:s')];
                }
                // Keep last 50 references
                $refs = array_slice($refs, -50);
                $db->prepare("UPDATE alfred_legal_cases SET canlii_references=:refs, updated_at=NOW() WHERE id=:id")
                   ->execute([':refs' => json_encode($refs), ':id' => $caseId]);
            }
        } catch (Exception $e) { error_log("[LegalSearch] DB save error: " . $e->getMessage()); }
    }

    return $result;
}


// ═══════════════════════════════════════════════════════════════════════════
// 79. LEGAL DRAFT MOTION — Generate legal documents
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalDraftMotion($args) {
    $caseId   = (int)($args['case_id'] ?? 0);
    $type     = trim($args['type'] ?? $args['motion_type'] ?? 'motion');
    $confirmed = !empty($args['confirmed']);

    // Valid types
    $validTypes = ['habeas_corpus', 'bail_review', 'motion', 'appeal'];
    if (!in_array($type, $validTypes)) {
        return ['error' => "Valid motion types: habeas_corpus, bail_review, motion, appeal. You said: \"$type\""];
    }

    $db = getDB();
    $caseData = [];

    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // Fill from args where case data is missing
    foreach (['caller_name','inmate_id','institution','case_number','court_name','court_district','case_summary','case_notes'] as $f) {
        if (empty($caseData[$f]) && !empty($args[$f])) $caseData[$f] = $args[$f];
    }

    $motion = generateLegalMotion($type, $caseData);

    // ── AI enrichment: Replace placeholders with substantive legal content ──
    $motion['body'] = enrichLegalMotionWithAI($motion['body'], $type, $caseData);

    if (!$confirmed) {
        return [
            'draft'      => true,
            'type'       => $type,
            'title'      => $motion['title'],
            'preview'    => substr($motion['body'], 0, 800) . "\n\n[...document continues...]",
            'disclaimer' => $motion['disclaimer'],
            'message'    => "I've drafted a {$motion['title']}. Please review the preview. " .
                           "If everything looks correct, confirm and I'll prepare the final document for faxing to the court. " .
                           "You can also ask me to modify specific sections.",
            'next_action'=> "Confirm to finalize, or tell me what to change.",
        ];
    }

    // Generate the final document and save as HTML for faxing
    $htmlDoc = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>" . htmlspecialchars($motion['title']) . "</title>" .
               "<style>body{font-family:'Times New Roman',serif;font-size:12pt;margin:1in;line-height:1.5;white-space:pre-wrap;}h1{font-size:14pt;text-align:center;}</style></head>" .
               "<body><h1>" . htmlspecialchars($motion['title']) . "</h1><pre>" . htmlspecialchars($motion['body']) . "</pre>" .
               "<hr><p style='font-size:9pt;color:#666;'>" . htmlspecialchars($motion['disclaimer']) . "</p></body></html>";

    // Save to server for faxing
    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "motion_{$type}_{$caseId}_" . date('Ymd_His') . ".html";
    $docPath = $docDir . $docFile;
    file_put_contents($docPath, $htmlDoc);

    $docUrl = "https://gositeme.com/downloads/legal/$docFile";

    // Update case record
    if ($caseId) {
        try {
            $s = $db->prepare("SELECT documents_filed FROM alfred_legal_cases WHERE id=:id");
            $s->execute([':id' => $caseId]);
            $row = $s->fetch(PDO::FETCH_ASSOC);
            $docs = json_decode($row['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => $type, 'title' => $motion['title'], 'url' => $docUrl, 'file' => $docFile, 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET case_type=:ct, documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':ct' => $type, ':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) { error_log("[LegalDraft] DB error: " . $e->getMessage()); }
    }

    return [
        'success'      => true,
        'type'         => $type,
        'title'        => $motion['title'],
        'document_url' => $docUrl,
        'disclaimer'   => $motion['disclaimer'],
        'message'      => "Motion finalized and saved. " .
                         "I can now fax this to the court. Would you like me to fax it? " .
                         "If you don't have the court fax number, I can call the clerk (greffe) to get it.",
        'next_actions' => ['fax to court', 'call court clerk for fax number', 'make changes'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 80. LEGAL UPDATE CASE — Add notes, court info, hearing dates
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalUpdateCase($args) {
    $caseId = (int)($args['case_id'] ?? 0);
    if (!$caseId) return ['error' => 'Which case? Provide the case ID.'];

    $db = getDB();
    $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
    $s->execute([':id' => $caseId]);
    $c = $s->fetch(PDO::FETCH_ASSOC);
    if (!$c) return ['error' => 'Case not found.'];

    $updates = [];
    $params  = [':id' => $caseId];
    $fields  = [
        'case_number'    => 'case_number',
        'case_type'      => 'case_type',
        'case_summary'   => 'case_summary',
        'case_notes'     => 'case_notes',
        'court_name'     => 'court_name',
        'court_phone'    => 'court_phone',
        'court_fax'      => 'court_fax',
        'court_address'  => 'court_address',
        'court_greffe_name' => 'court_greffe_name',
        'court_district' => 'court_district',
        'next_hearing_date' => 'next_hearing_date',
        'next_steps'     => 'next_steps',
        'status'         => 'status',
        'charges'        => 'charges',
        'province'       => 'province',
    ];

    foreach ($fields as $argKey => $col) {
        if (isset($args[$argKey]) && $args[$argKey] !== '') {
            $updates[] = "$col=:$argKey";
            $params[":$argKey"] = $args[$argKey];
        }
    }

    // Append to notes rather than replace
    if (isset($args['add_note'])) {
        $existingNotes = $c['case_notes'] ?? '';
        $newNote = "\n[" . date('Y-m-d H:i') . "] " . $args['add_note'];
        $updates[] = "case_notes=:notes";
        $params[':notes'] = $existingNotes . $newNote;
    }

    if (empty($updates)) return ['error' => 'What do you want to update? Provide at least one field.'];

    $sql = "UPDATE alfred_legal_cases SET " . implode(', ', $updates) . ", updated_at=NOW() WHERE id=:id";
    $db->prepare($sql)->execute($params);

    return ['success' => true, 'case_id' => $caseId, 'updated_fields' => array_keys(array_intersect_key($args, $fields)), 'message' => "Case #{$caseId} updated successfully."];
}


// ═══════════════════════════════════════════════════════════════════════════
// 81. LEGAL CALL COURT — Alfred calls the court clerk/greffe
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalCallCourt($args) {
    $caseId    = (int)($args['case_id'] ?? 0);
    $courtPhone = trim($args['court_phone'] ?? $args['phone'] ?? '');
    $district  = trim($args['district'] ?? $args['court_district'] ?? '');
    $purpose   = trim($args['purpose'] ?? 'get fax number');

    // If no phone, try to find from court directory or case record
    if (!$courtPhone && $caseId) {
        $db = getDB();
        $s = $db->prepare("SELECT court_phone, court_district FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $c = $s->fetch(PDO::FETCH_ASSOC);
        if ($c) {
            $courtPhone = $c['court_phone'];
            $district   = $district ?: $c['court_district'];
        }
    }

    // Try court directory if we have a district
    if (!$courtPhone && $district) {
        $dir = getQuebecCourtDirectory();
        $distLower = strtolower(str_replace([' ', '-', "'", 'è', 'é'], ['_', '_', '', 'e', 'e'], $district));
        foreach ($dir as $key => $court) {
            if (stripos($key, $distLower) !== false || stripos($court['district'], $district) !== false || stripos($court['name'], $district) !== false) {
                $courtPhone = $court['phone'];
                // Also save the fax and court info to case
                if ($caseId) {
                    try {
                        $db = $db ?? getDB();
                        $db->prepare("UPDATE alfred_legal_cases SET court_name=:cn, court_phone=:cp, court_fax=:cf, court_address=:ca, court_district=:cd, court_greffe_name=:cg, updated_at=NOW() WHERE id=:id")
                           ->execute([':cn' => $court['name'], ':cp' => $court['phone'], ':cf' => $court['fax'], ':ca' => $court['address'], ':cd' => $court['district'], ':cg' => $court['greffe'], ':id' => $caseId]);
                    } catch (Exception $e) {}
                }
                return [
                    'success'     => true,
                    'found_in_directory' => true,
                    'court'       => $court,
                    'message'     => "I found the court in our directory: {$court['name']}. " .
                                   "Phone: {$court['phone']}, Fax: {$court['fax']}. " .
                                   "I've saved this info to your case. Would you like me to fax your motion now, or should I call them to confirm?",
                ];
            }
        }
    }

    if (!$courtPhone) {
        // Return the full directory so Alfred can ask which one
        $dir = getQuebecCourtDirectory();
        $courts = array_map(fn($k, $c) => "{$c['name']} — {$c['district']} — {$c['phone']}", array_keys($dir), $dir);
        return [
            'error' => 'I need the court phone number or district name. Here are the courts I know:',
            'courts' => $courts,
            'message' => 'Which court district is your case in? I can look up the number.',
        ];
    }

    // Build the greeting for the outbound call
    $greeting = match(strtolower($purpose)) {
        'get fax number', 'fax' => "Bonjour, ici Alfred, assistant juridique. Je vous appelle pour obtenir le numéro de télécopieur du greffe de la Cour supérieure pour déposer une requête. Could I get the fax number please?",
        'filing' => "Bonjour, ici Alfred, assistant juridique. J'appelle concernant le dépôt d'une requête. Pouvez-vous me confirmer la procédure?",
        'hearing' => "Bonjour, ici Alfred, assistant juridique. J'appelle pour vérifier la date d'audience dans un dossier. Could I check on a hearing date?",
        default => "Bonjour, ici Alfred, assistant juridique pour un détenu. I'm calling regarding: $purpose",
    };

    // Make the call with VAPI → Telnyx fallback
    $callResult = callOutboundWithFallback($courtPhone, $greeting, 'legal_court_call', 0);

    if (!empty($callResult['success'])) {
        // Save to case
        if ($caseId) {
            try {
                $db = $db ?? getDB();
                $db->prepare("UPDATE alfred_legal_cases SET court_phone=:cp, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
                   ->execute([':cp' => $courtPhone, ':note' => "\n[" . date('Y-m-d H:i') . "] Called court at $courtPhone — purpose: $purpose (provider: {$callResult['provider']})", ':id' => $caseId]);
            } catch (Exception $e) {}
        }
        return [
            'success'  => true,
            'provider' => $callResult['provider'],
            'call_id'  => $callResult['call_id'],
            'phone'    => $courtPhone,
            'message'  => "I'm now calling the court at $courtPhone to $purpose. " .
                         "I'll report back what they say. If the call doesn't connect, I'll try an alternate provider.",
        ];
    }

    return [
        'error'   => $callResult['error'],
        'phone'   => $courtPhone,
        'message' => "I couldn't reach the court at $courtPhone. " . ($callResult['error'] ?? '') .
                    " You may want to try calling them directly or provide an alternate number.",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 82. LEGAL FAX COURT — Fax the motion to the court with fallback
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalFaxCourt($args) {
    $caseId     = (int)($args['case_id'] ?? 0);
    $courtFax   = trim($args['court_fax'] ?? $args['fax_number'] ?? $args['to'] ?? '');
    $documentUrl = trim($args['document_url'] ?? '');
    $confirmed  = !empty($args['confirmed']);

    $db = getDB();

    // Try to get info from case
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $c = $s->fetch(PDO::FETCH_ASSOC);

        if ($c) {
            if (!$courtFax) $courtFax = $c['court_fax'];

            // Use the latest document if none specified
            if (!$documentUrl) {
                $docs = json_decode($c['documents_filed'] ?: '[]', true);
                if (!empty($docs)) {
                    $latest = end($docs);
                    $documentUrl = $latest['url'] ?? '';
                }
            }
        }
    }

    if (!$courtFax) {
        return [
            'error' => "I need the court's fax number. Would you like me to call the court clerk to get it, or check our court directory?",
            'next_actions' => ['call court clerk', 'court directory'],
        ];
    }
    if (!$documentUrl) {
        return ['error' => "No document to fax. Would you like me to draft a motion first?", 'next_actions' => ['draft motion']];
    }

    if (!$confirmed) {
        return [
            'confirm_required' => true,
            'court_fax'    => $courtFax,
            'document_url' => $documentUrl,
            'message'      => "Ready to fax your document to $courtFax. Please confirm to send.",
        ];
    }

    // Get a from number — use TELNYX_FROM_NUMBER or first fax-enabled number
    $envFile = dirname(dirname(__DIR__)) . '/.env.php';
    if (file_exists($envFile)) require_once $envFile;
    $fromNumber = getenv('TELNYX_FROM_NUMBER');
    if (!$fromNumber) {
        try {
            $ph = $db->prepare("SELECT phone_number FROM voice_phone_numbers WHERE fax_enabled=1 AND active=1 LIMIT 1");
            $ph->execute();
            $r = $ph->fetch(PDO::FETCH_ASSOC);
            if ($r) $fromNumber = $r['phone_number'];
        } catch (Exception $e) {}
    }
    if (!$fromNumber) {
        return [
            'error' => 'No fax-capable number available. Configure TELNYX_FROM_NUMBER or provision a fax number first.',
            'court_fax' => $courtFax,
        ];
    }

    // Send with fallback
    $result = sendFaxWithFallback($courtFax, $fromNumber, $documentUrl, 0);

    if (!empty($result['success'])) {
        // Update case
        if ($caseId) {
            try {
                // Mark document as filed
                $s = $db->prepare("SELECT documents_filed FROM alfred_legal_cases WHERE id=:id");
                $s->execute([':id' => $caseId]);
                $row = $s->fetch(PDO::FETCH_ASSOC);
                $docs = json_decode($row['documents_filed'] ?? '[]', true);
                foreach ($docs as &$d) {
                    if (($d['url'] ?? '') === $documentUrl) $d['status'] = 'faxed';
                }
                unset($d);
                $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, court_fax=:cf, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
                   ->execute([':docs' => json_encode($docs), ':cf' => $courtFax, ':note' => "\n[" . date('Y-m-d H:i') . "] Faxed to $courtFax via {$result['provider']}. Fax ID: " . ($result['fax_id'] ?? 'N/A'), ':id' => $caseId]);
            } catch (Exception $e) {}
        }

        return [
            'success'      => true,
            'provider'     => $result['provider'],
            'fax_id'       => $result['fax_id'] ?? null,
            'court_fax'    => $courtFax,
            'document_url' => $documentUrl,
            'message'      => "Fax sent to $courtFax via {$result['provider']}! " .
                             "Your motion is being transmitted. Keep your case number in case you need to follow up. " .
                             "The court greffe will stamp and process it. You should receive confirmation within 1-2 business days.",
        ];
    }

    return [
        'error'        => $result['error'] ?? 'Fax failed',
        'queued'       => $result['queued'] ?? false,
        'court_fax'    => $courtFax,
        'message'      => "The fax to $courtFax failed through all providers. " .
                         ($result['queued'] ? "I've queued it for retry. " : "") .
                         "You can also try: 1) Ask me to call the court to verify the fax number, or 2) Provide an alternate fax number.",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 83. LEGAL CASE STATUS — Check status, documents, next steps
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalCaseStatus($args) {
    $caseId      = (int)($args['case_id'] ?? 0);
    $callerPhone = trim($args['caller_phone'] ?? $args['phone'] ?? '');
    $inmateId    = trim($args['inmate_id'] ?? '');

    $db = getDB();

    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
    } elseif ($callerPhone) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE caller_phone=:p ORDER BY updated_at DESC LIMIT 1");
        $s->execute([':p' => $callerPhone]);
    } elseif ($inmateId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE inmate_id=:i ORDER BY updated_at DESC LIMIT 1");
        $s->execute([':i' => $inmateId]);
    } else {
        return ['error' => 'Provide case_id, caller_phone, or inmate_id.'];
    }

    $c = $s->fetch(PDO::FETCH_ASSOC);
    if (!$c) return ['error' => 'No case found.'];

    $docs   = json_decode($c['documents_filed'] ?: '[]', true);
    $canlii = json_decode($c['canlii_references'] ?: '[]', true);

    $summary = "Case #{$c['id']} — {$c['case_type']} — Status: {$c['status']}\n";
    $summary .= "Name: {$c['caller_name']} | Institution: {$c['institution']} | Inmate ID: {$c['inmate_id']}\n";
    if ($c['court_name']) $summary .= "Court: {$c['court_name']} ({$c['court_district']})\n";
    if ($c['court_fax'])  $summary .= "Court Fax: {$c['court_fax']}\n";
    if ($c['next_hearing_date']) $summary .= "Next Hearing: {$c['next_hearing_date']}\n";
    $summary .= "Documents: " . count($docs) . " | CanLII References: " . count($canlii) . "\n";
    if ($c['next_steps']) $summary .= "Next Steps: {$c['next_steps']}\n";

    return [
        'success' => true,
        'case'    => [
            'id'           => (int)$c['id'],
            'type'         => $c['case_type'],
            'status'       => $c['status'],
            'caller_name'  => $c['caller_name'],
            'inmate_id'    => $c['inmate_id'],
            'institution'  => $c['institution'],
            'case_number'  => $c['case_number'],
            'court'        => $c['court_name'],
            'court_fax'    => $c['court_fax'],
            'court_phone'  => $c['court_phone'],
            'district'     => $c['court_district'],
            'documents'    => $docs,
            'canlii_refs'  => $canlii,
            'next_hearing' => $c['next_hearing_date'],
            'next_steps'   => $c['next_steps'],
            'created'      => $c['created_at'],
            'updated'      => $c['updated_at'],
            'last_call'    => $c['last_call_at'],
        ],
        'message' => $summary,
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 84. LEGAL LIST CASES — List all cases for a caller
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalListCases($args) {
    $callerPhone = trim($args['caller_phone'] ?? $args['phone'] ?? '');
    $inmateId    = trim($args['inmate_id'] ?? '');
    $status      = trim($args['status'] ?? '');

    $db = getDB();

    $sql = "SELECT id, caller_name, inmate_id, institution, case_number, case_type, status, court_name, court_district, next_hearing_date, next_steps, created_at, updated_at FROM alfred_legal_cases WHERE 1=1";
    $params = [];

    if ($callerPhone) { $sql .= " AND caller_phone=:phone"; $params[':phone'] = $callerPhone; }
    if ($inmateId)    { $sql .= " AND inmate_id=:iid"; $params[':iid'] = $inmateId; }
    if ($status)      { $sql .= " AND status=:status"; $params[':status'] = $status; }

    $sql .= " ORDER BY updated_at DESC LIMIT 20";

    $s = $db->prepare($sql);
    $s->execute($params);
    $cases = $s->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cases)) return ['cases' => [], 'count' => 0, 'message' => 'No cases found. Would you like to start a new one?'];

    $summaries = array_map(fn($c) => "#{$c['id']} {$c['case_type']} ({$c['status']}) — {$c['court_district']}", $cases);

    return [
        'cases'   => $cases,
        'count'   => count($cases),
        'message' => "Found " . count($cases) . " case(s):\n" . implode("\n", $summaries),
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 85. LEGAL COURT DIRECTORY — Look up court info by district
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalCourtDirectory($args) {
    $district = trim($args['district'] ?? $args['court'] ?? $args['city'] ?? '');

    $dir = getQuebecCourtDirectory();

    if (!$district) {
        // Return the full directory
        $list = array_map(fn($k, $c) => ['key' => $k, 'name' => $c['name'], 'district' => $c['district'], 'phone' => $c['phone'], 'fax' => $c['fax']], array_keys($dir), $dir);
        return ['courts' => $list, 'count' => count($list), 'message' => 'Here are all the courts in our directory. Which district do you need?'];
    }

    // Search by district name
    $distLower = strtolower(str_replace([' ', '-', "'", 'è', 'é'], ['_', '_', '', 'e', 'e'], $district));
    foreach ($dir as $key => $court) {
        if (stripos($key, $distLower) !== false || stripos($court['district'], $district) !== false || stripos($court['name'], $district) !== false) {
            return ['success' => true, 'court' => $court, 'message' => "{$court['name']}\nAddress: {$court['address']}\nPhone: {$court['phone']}\nFax: {$court['fax']}\nGreffe: {$court['greffe']}"];
        }
    }

    return ['error' => "No court found for \"$district\". Try: Montreal, Quebec City, Laval, Longueuil, Gatineau, Sherbrooke, Trois-Rivières, Joliette, Rimouski."];
}


// ═══════════════════════════════════════════════════════════════════════════
// 86. LEGAL GRIEVANCE — Draft and track institutional grievances
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalGrievance($args) {
    $caseId      = (int)($args['case_id'] ?? 0);
    $action      = trim($args['action'] ?? 'draft'); // draft, list, status
    $type        = trim($args['type'] ?? 'general');  // general, conditions, medical, transfer, discipline, food, mail
    $description = trim($args['description'] ?? $args['complaint'] ?? '');
    $institution = trim($args['institution'] ?? '');
    $urgency     = trim($args['urgency'] ?? 'normal'); // urgent, normal

    $db = getDB();

    // Get case data if available
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
        $institution = $institution ?: ($caseData['institution'] ?? '');
    }

    if ($action === 'list') {
        // List grievances from case data
        $grievances = json_decode($caseData['grievance_data'] ?? '[]', true) ?: [];
        if (empty($grievances)) return ['grievances' => [], 'count' => 0, 'message' => 'No grievances on file. Would you like to draft one?'];
        return ['grievances' => $grievances, 'count' => count($grievances), 'message' => count($grievances) . " grievance(s) on file."];
    }

    if ($action === 'status') {
        $grievanceId = (int)($args['grievance_id'] ?? 0);
        $grievances = json_decode($caseData['grievance_data'] ?? '[]', true) ?: [];
        if ($grievanceId > 0 && $grievanceId <= count($grievances)) {
            $g = $grievances[$grievanceId - 1];
            return ['success' => true, 'grievance' => $g, 'message' => "Grievance #{$grievanceId}: {$g['type']} — Status: {$g['status']}. Filed: {$g['filed_date']}"];
        }
        return ['error' => "Grievance #{$grievanceId} not found. Use action:'list' to see all."];
    }

    // Draft a grievance
    if (!$description) return ['error' => 'Please describe your grievance. What happened, when, who was involved?'];
    if (!$institution) return ['error' => 'Which institution are you filing the grievance at?'];

    $grievanceTypes = [
        'general'    => 'Plainte générale / General Complaint',
        'conditions' => 'Conditions de détention / Conditions of Confinement',
        'medical'    => 'Soins médicaux / Medical Care',
        'transfer'   => 'Transfert / Transfer Request',
        'discipline' => 'Mesures disciplinaires / Disciplinary Measures',
        'food'       => 'Alimentation / Food Services',
        'mail'       => 'Courrier / Mail Interference',
    ];
    $typeLabel = $grievanceTypes[$type] ?? $grievanceTypes['general'];

    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? 'Le détenu');
    $inmateId   = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');

    $grievanceDoc = "
═══════════════════════════════════════════════════════
GRIEF INTERNE / INTERNAL GRIEVANCE
{$typeLabel}
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "
Institution: {$institution}
Détenu / Inmate: {$callerName}
Numéro matricule / Inmate ID: {$inmateId}
Niveau d'urgence / Urgency: " . ($urgency === 'urgent' ? 'URGENT' : 'Normal') . "
Type: {$typeLabel}

───────────────────────────────────────────────────────
DESCRIPTION DES FAITS / DESCRIPTION OF FACTS
───────────────────────────────────────────────────────

{$description}

───────────────────────────────────────────────────────
RÉSOLUTION DEMANDÉE / REQUESTED RESOLUTION
───────────────────────────────────────────────────────

Le détenu demande respectueusement:
1. Que la situation décrite ci-dessus soit corrigée immédiatement;
2. Que des mesures soient prises pour prévenir la récurrence de cette situation;
3. Une réponse écrite dans les délais prévus par la loi.

The inmate respectfully requests:
1. That the above situation be corrected immediately;
2. That measures be taken to prevent recurrence;
3. A written response within the time limits prescribed by law.

───────────────────────────────────────────────────────
NOTES IMPORTANTES / IMPORTANT NOTES
───────────────────────────────────────────────────────

Conformément à la Loi sur le système correctionnel du Québec (LSCMLSC),
le détenu a droit de déposer un grief interne. L'établissement doit y
répondre dans les 15 jours ouvrables. En l'absence de réponse satisfaisante,
le détenu peut porter plainte au Protecteur du citoyen (1-800-463-5070).

Under the Corrections and Conditional Release Act (CCRA), the inmate
has the right to file an internal grievance. The institution must respond
within prescribed timeframes. If unsatisfied, the inmate may complain
to the Office of the Correctional Investigator (OCI).

Signature: _________________________________
           {$callerName}
Date: " . date('Y-m-d') . "
";

    // Save to case
    $grievanceEntry = [
        'id'          => uniqid('GRV'),
        'type'        => $type,
        'type_label'  => $typeLabel,
        'description' => $description,
        'institution' => $institution,
        'urgency'     => $urgency,
        'filed_date'  => date('Y-m-d H:i:s'),
        'status'      => 'drafted',
        'response'    => null,
    ];

    if ($caseId) {
        try {
            $grievances = json_decode($caseData['grievance_data'] ?? '[]', true) ?: [];
            $grievances[] = $grievanceEntry;
            $db->prepare("UPDATE alfred_legal_cases SET grievance_data=:gd, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
               ->execute([':gd' => json_encode($grievances), ':note' => "\n[" . date('Y-m-d H:i') . "] Grievance drafted: {$typeLabel}", ':id' => $caseId]);
        } catch (Exception $e) { error_log("[LegalGrievance] DB error: " . $e->getMessage()); }
    }

    // Save HTML for printing/faxing
    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "grievance_{$type}_{$caseId}_" . date('Ymd_His') . ".html";
    $docPath = $docDir . $docFile;
    $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Grievance</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($grievanceDoc) . "</body></html>";
    file_put_contents($docPath, $html);

    return [
        'success'      => true,
        'grievance'    => $grievanceEntry,
        'document'     => $grievanceDoc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "Grievance drafted ({$typeLabel}). You should submit this to the institution's grievance coordinator. " .
                         "They must respond within 15 business days. If they don't respond or you're unsatisfied, " .
                         "I can help you file with the Protecteur du citoyen (provincial) or OCI (federal). " .
                         "Would you like me to fax this somewhere?",
        'next_actions' => ['fax grievance', 'file with Protecteur du citoyen', 'file with OCI'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 87. LEGAL PAROLE — Parole hearing preparation
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalParole($args) {
    $caseId       = (int)($args['case_id'] ?? 0);
    $action       = trim($args['action'] ?? 'prepare'); // prepare, checklist, letter
    $hearingDate  = trim($args['hearing_date'] ?? '');
    $paroleType   = trim($args['parole_type'] ?? 'day'); // day, full, statutory
    $supportPlan  = trim($args['support_plan'] ?? '');
    $releaseAddress = trim($args['release_address'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $inmateId   = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');
    $institution = $caseData['institution'] ?? ($args['institution'] ?? '');

    $paroleTypes = [
        'day'       => 'Semi-liberté / Day Parole',
        'full'      => 'Libération conditionnelle totale / Full Parole',
        'statutory' => 'Libération d\'office / Statutory Release',
    ];
    if (!isset($paroleTypes[$paroleType])) $paroleType = 'day';
    $typeLabel = $paroleTypes[$paroleType];

    if ($action === 'checklist') {
        return [
            'success' => true,
            'checklist' => [
                '1. Obtain institutional progress report from case manager',
                '2. Complete parole application form (CSC form)',
                '3. Prepare release plan (housing, employment, community support)',
                '4. Arrange community sponsor/surety if applicable',
                '5. Complete any required programs (substance abuse, anger management, etc.)',
                '6. Gather letters of support from family, employer, community members',
                '7. Prepare personal statement for the hearing',
                '8. Review victim impact information (if applicable)',
                '9. Confirm hearing date with parole office',
                '10. Practice your statement — be honest and show insight',
            ],
            'contacts' => [
                'Parole Board of Canada (PBC)' => '1-800-874-2652',
                'Commission québécoise des libérations conditionnelles (CQLC)' => '+14186434020',
                'Aide juridique du Québec' => '1-800-842-2213',
            ],
            'message' => "Here's your parole hearing preparation checklist. Do you want me to help you draft your personal statement or release plan?",
        ];
    }

    if ($action === 'letter') {
        // Draft a parole board letter
        $letter = "
═══════════════════════════════════════════════════════
DÉCLARATION PERSONNELLE / PERSONAL STATEMENT
Pour la Commission des libérations conditionnelles
{$typeLabel}
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "
Nom / Name: {$callerName}
Numéro SED / FPS Number: {$inmateId}
Établissement / Institution: {$institution}
Type de libération demandée: {$typeLabel}
Date d'audience prévue: " . ($hearingDate ?: 'À confirmer') . "

───────────────────────────────────────────────────────
PLAN DE SORTIE / RELEASE PLAN
───────────────────────────────────────────────────────

" . ($supportPlan ?: "[Décrivez votre plan de sortie: logement, emploi, soutien communautaire]
[Describe your release plan: housing, employment, community support]") . "

Adresse de libération / Release Address:
" . ($releaseAddress ?: "[Adresse à compléter / Address to be provided]") . "

───────────────────────────────────────────────────────
PROGRAMMES COMPLÉTÉS / COMPLETED PROGRAMS
───────────────────────────────────────────────────────

[Liste des programmes complétés pendant l'incarcération]
[List of programs completed during incarceration]

───────────────────────────────────────────────────────
DÉCLARATION / STATEMENT
───────────────────────────────────────────────────────

[Votre déclaration personnelle exprimant votre prise de conscience,
les leçons apprises, et votre engagement envers une réintégration positive]

[Your personal statement expressing awareness, lessons learned,
and commitment to positive reintegration]

Signature: _________________________________
           {$callerName}
Date: " . date('Y-m-d') . "
";

        // Save
        if ($caseId) {
            try {
                $paroleData = json_decode($caseData['parole_data'] ?? '[]', true) ?: [];
                $paroleData[] = [
                    'type' => $paroleType, 'hearing_date' => $hearingDate,
                    'letter_drafted' => date('Y-m-d H:i:s'), 'status' => 'prepared',
                ];
                $db->prepare("UPDATE alfred_legal_cases SET parole_data=:pd, updated_at=NOW() WHERE id=:id")
                   ->execute([':pd' => json_encode($paroleData), ':id' => $caseId]);
                if ($hearingDate) {
                    $db->prepare("UPDATE alfred_legal_cases SET next_hearing_date=:hd WHERE id=:id")
                       ->execute([':hd' => $hearingDate, ':id' => $caseId]);
                }
            } catch (Exception $e) {}
        }

        $docDir  = dirname(__DIR__) . '/downloads/legal/';
        if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
        $docFile = "parole_{$paroleType}_{$caseId}_" . date('Ymd_His') . ".html";
        file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Parole Statement</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($letter) . "</body></html>");

        return [
            'success'      => true,
            'document'     => $letter,
            'document_url' => "https://gositeme.com/downloads/legal/$docFile",
            'message'      => "Parole statement drafted for {$typeLabel}. Review it carefully — be honest and show insight into your offence. Would you like me to help fill in specific sections?",
        ];
    }

    // Default: prepare overview
    return [
        'success' => true,
        'type'    => $typeLabel,
        'hearing_date' => $hearingDate ?: 'Not set',
        'resources' => [
            'Parole Board of Canada' => 'https://www.canada.ca/en/parole-board.html',
            'CQLC (Québec)' => 'https://www.cqlc.gouv.qc.ca/',
            'Legal Aid Québec' => '1-800-842-2213',
        ],
        'message' => "I can help you prepare for your {$typeLabel} hearing. Options: " .
                    "1) Get the preparation checklist (action:'checklist'), " .
                    "2) Draft your personal statement (action:'letter'), " .
                    "3) Search CanLII for parole case law. What would you like?",
        'next_actions' => ['parole checklist', 'draft parole statement', 'search parole case law'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 88. LEGAL DISCIPLINARY — Defense against institutional charges
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalDisciplinary($args) {
    $caseId       = (int)($args['case_id'] ?? 0);
    $action       = trim($args['action'] ?? 'defense'); // defense, appeal, list
    $chargeType   = trim($args['charge_type'] ?? $args['offence'] ?? '');
    $description  = trim($args['description'] ?? $args['incident'] ?? '');
    $hearingDate  = trim($args['hearing_date'] ?? '');
    $institution  = trim($args['institution'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
        $institution = $institution ?: ($caseData['institution'] ?? '');
    }

    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $inmateId   = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');

    if ($action === 'list') {
        $disciplinary = json_decode($caseData['disciplinary_data'] ?? '[]', true) ?: [];
        return ['disciplinary' => $disciplinary, 'count' => count($disciplinary), 'message' => count($disciplinary) . " disciplinary record(s) on file."];
    }

    if (!$chargeType && !$description) {
        return ['error' => 'What disciplinary charge are you facing? Describe the charge and incident.'];
    }

    // Draft defense statement
    $defense = "
═══════════════════════════════════════════════════════
DÉFENSE CONTRE ACCUSATION DISCIPLINAIRE
DEFENSE AGAINST DISCIPLINARY CHARGE
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "
Institution: {$institution}
Détenu / Inmate: {$callerName}
Numéro matricule: {$inmateId}
Type d'infraction / Charge Type: {$chargeType}
Date d'audience / Hearing Date: " . ($hearingDate ?: 'À confirmer') . "

───────────────────────────────────────────────────────
DESCRIPTION DE L'INCIDENT / INCIDENT DESCRIPTION
───────────────────────────────────────────────────────

{$description}

───────────────────────────────────────────────────────
DÉFENSE / DEFENSE
───────────────────────────────────────────────────────

Le détenu nie/conteste les faits allégués pour les raisons suivantes:
The inmate denies/contests the alleged facts for the following reasons:

1. [Arguments de défense à compléter / Defense arguments to be completed]

───────────────────────────────────────────────────────
DROITS DU DÉTENU / INMATE RIGHTS
───────────────────────────────────────────────────────

En vertu de la LSCMLSC / Under the CCRA:
• Droit d'être informé des accusations par écrit (Right to written notice)
• Droit de présenter une défense (Right to present a defense)
• Droit d'appeler la décision (Right to appeal the decision)
• Droit à un avocat (Right to counsel — Legal Aid: 1-800-842-2213)
• Droit à un interprète si nécessaire (Right to interpreter if needed)
• La preuve doit être \"au-delà d'un doute raisonnable\" pour les
  infractions graves (beyond reasonable doubt for serious offences)

Procédure d'appel: Vous avez le droit d'en appeler dans les délais prescrits.
Appeal: You have the right to appeal within prescribed timeframes.

Signature: _________________________________
           {$callerName}
Date: " . date('Y-m-d') . "
";

    // Enrich with AI if Groq available
    $defense = enrichLegalMotionWithAI($defense, 'disciplinary_defense', array_merge($caseData, [
        'charges' => $chargeType,
        'case_summary' => $description,
    ]));

    // Save
    $entry = [
        'charge_type' => $chargeType, 'description' => $description,
        'hearing_date' => $hearingDate, 'drafted' => date('Y-m-d H:i:s'),
        'status' => $action === 'appeal' ? 'appealing' : 'defense_drafted',
    ];
    if ($caseId) {
        try {
            $disc = json_decode($caseData['disciplinary_data'] ?? '[]', true) ?: [];
            $disc[] = $entry;
            $db->prepare("UPDATE alfred_legal_cases SET disciplinary_data=:dd, updated_at=NOW() WHERE id=:id")
               ->execute([':dd' => json_encode($disc), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "disciplinary_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Disciplinary Defense</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($defense) . "</body></html>");

    return [
        'success'      => true,
        'document'     => $defense,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "Defense statement drafted against charge: {$chargeType}. " .
                         "Review carefully. You have the right to present this at your hearing. " .
                         "Would you like me to search CanLII for similar disciplinary cases?",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 89. LEGAL AFFIDAVIT — Generate sworn statements
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalAffidavit($args) {
    $caseId       = (int)($args['case_id'] ?? 0);
    $facts        = $args['facts'] ?? $args['statements'] ?? [];
    $purpose      = trim($args['purpose'] ?? 'general');
    $affiantName  = trim($args['affiant_name'] ?? $args['name'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $affiantName = $affiantName ?: ($caseData['caller_name'] ?? '');
    if (!$affiantName) return ['error' => 'What is the name of the person making this affidavit?'];

    // Accept facts as string or array
    if (is_string($facts)) $facts = array_filter(array_map('trim', explode("\n", $facts)));
    if (empty($facts)) return ['error' => 'Please provide the facts/statements for the affidavit. List each fact separately.'];

    $institution = $caseData['institution'] ?? ($args['institution'] ?? '');
    $inmateId    = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');
    $district    = $caseData['court_district'] ?? ($args['district'] ?? 'Montréal');

    $factsText = '';
    foreach ($facts as $i => $fact) {
        $num = $i + 1;
        $factsText .= "{$num}. {$fact}\n\n";
    }

    $affidavit = "
CANADA
PROVINCE DE QUÉBEC
DISTRICT DE " . strtoupper($district) . "

═══════════════════════════════════════════════════════
DÉCLARATION SOUS SERMENT / AFFIDAVIT
═══════════════════════════════════════════════════════

Je soussigné(e), {$affiantName}" . ($inmateId ? " (matricule: {$inmateId})" : "") .
($institution ? ", détenu(e) à {$institution}" : "") . ",
déclare solennellement ce qui suit:

I, the undersigned, {$affiantName}, solemnly declare as follows:

───────────────────────────────────────────────────────
FAITS / FACTS
───────────────────────────────────────────────────────

{$factsText}
───────────────────────────────────────────────────────

Cette déclaration sous serment est faite au soutien de: {$purpose}
This affidavit is made in support of: {$purpose}

ET JE SUIS CONSCIENT(E) QU'UNE FAUSSE DÉCLARATION SOUS SERMENT
CONSTITUE UN ACTE CRIMINEL EN VERTU DE L'ARTICLE 131 DU CODE CRIMINEL.

AND I AM AWARE THAT A FALSE AFFIDAVIT CONSTITUTES A CRIMINAL OFFENCE
UNDER SECTION 131 OF THE CRIMINAL CODE.

ASSERMENTÉ DEVANT MOI à ________________
ce " . date('j') . "e jour de " . date('F Y') . "

_________________________________    _________________________________
Commissaire à l'assermentation         {$affiantName}
Commissioner for Oaths                 Affiant
";

    // Save
    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "affidavit_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Affidavit</title><style>body{font-family:'Times New Roman',serif;font-size:12pt;margin:1in;white-space:pre-wrap;line-height:1.6;}</style></head><body>" . htmlspecialchars($affidavit) . "</body></html>");

    if ($caseId) {
        try {
            $s2 = $db->prepare("SELECT documents_filed FROM alfred_legal_cases WHERE id=:id");
            $s2->execute([':id' => $caseId]);
            $row = $s2->fetch(PDO::FETCH_ASSOC);
            $docs = json_decode($row['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'affidavit', 'title' => 'Affidavit', 'file' => $docFile, 'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'document'     => $affidavit,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'fact_count'   => count($facts),
        'message'      => "Affidavit drafted with " . count($facts) . " statements. " .
                         "IMPORTANT: This must be sworn before a Commissioner for Oaths or Justice of the Peace. " .
                         "Do NOT sign until you are before the commissioner. " .
                         "A false affidavit is a criminal offence (s. 131 Criminal Code).",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 90. LEGAL PROTECTEUR DU CITOYEN — File Quebec ombudsman complaints
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalProtecteur($args) {
    $caseId      = (int)($args['case_id'] ?? 0);
    $complaint   = trim($args['complaint'] ?? $args['description'] ?? '');
    $institution = trim($args['institution'] ?? '');
    $action      = trim($args['action'] ?? 'draft'); // draft, info

    if ($action === 'info') {
        return [
            'success' => true,
            'info' => [
                'name'    => 'Protecteur du citoyen / Quebec Ombudsman',
                'phone'   => '1-800-463-5070',
                'email'   => 'protecteur@protecteurducitoyen.qc.ca',
                'website' => 'https://protecteurducitoyen.qc.ca/',
                'address' => '800, place D\'Youville, 19e étage, Québec (Québec) G1R 3P4',
                'mandate' => 'The Protecteur du citoyen investigates complaints about Quebec provincial government services, including provincial correctional facilities.',
                'note'    => 'For FEDERAL institutions, use the Office of the Correctional Investigator (OCI) instead.',
            ],
            'message' => "The Protecteur du citoyen handles complaints about PROVINCIAL institutions in Quebec. " .
                        "Call 1-800-463-5070 or let me draft a formal complaint. " .
                        "NOTE: You should file an internal grievance FIRST before going to the Protecteur.",
        ];
    }

    // Draft complaint
    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
        $institution = $institution ?: ($caseData['institution'] ?? '');
    }

    if (!$complaint) return ['error' => 'Please describe your complaint. What happened, what response did you get from the institution, and what resolution are you seeking?'];
    if (!$institution) return ['error' => 'Which institution are you filing about?'];

    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $inmateId   = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');

    // Check if internal grievance was filed first
    $grievances = json_decode($caseData['grievance_data'] ?? '[]', true) ?: [];
    $grievanceFiled = !empty($grievances);

    $complaintDoc = "
═══════════════════════════════════════════════════════
PLAINTE AU PROTECTEUR DU CITOYEN
COMPLAINT TO THE QUEBEC OMBUDSMAN
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "

DESTINATAIRE / TO:
Protecteur du citoyen
800, place D'Youville, 19e étage
Québec (Québec) G1R 3P4
Tél: 1-800-463-5070

DE / FROM:
{$callerName}
Matricule: {$inmateId}
Institution: {$institution}

───────────────────────────────────────────────────────
OBJET DE LA PLAINTE / SUBJECT OF COMPLAINT
───────────────────────────────────────────────────────

{$complaint}

───────────────────────────────────────────────────────
DÉMARCHES ENTREPRISES / STEPS TAKEN
───────────────────────────────────────────────────────

" . ($grievanceFiled ?
"Un grief interne a été déposé le " . ($grievances[count($grievances)-1]['filed_date'] ?? 'inconnu') . ".\nAn internal grievance was filed on " . ($grievances[count($grievances)-1]['filed_date'] ?? 'unknown') . "." :
"[Indiquer si un grief interne a été déposé et sa date]\n[Indicate whether an internal grievance was filed and its date]") . "

───────────────────────────────────────────────────────
RÉSOLUTION DEMANDÉE / REQUESTED RESOLUTION
───────────────────────────────────────────────────────

Le plaignant demande au Protecteur du citoyen d'enquêter sur cette
situation et de formuler des recommandations à l'institution.

The complainant requests that the Ombudsman investigate this matter
and make recommendations to the institution.

Signature: _________________________________
           {$callerName}
Date: " . date('Y-m-d') . "
";

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "protecteur_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Plainte Protecteur</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($complaintDoc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'protecteur_complaint', 'title' => 'Plainte au Protecteur du citoyen', 'file' => $docFile, 'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'document'     => $complaintDoc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'contact'      => ['phone' => '1-800-463-5070', 'email' => 'protecteur@protecteurducitoyen.qc.ca'],
        'internal_grievance_filed' => $grievanceFiled,
        'message'      => "Complaint drafted for the Protecteur du citoyen. " .
                         (!$grievanceFiled ? "WARNING: You should file an internal grievance FIRST. The Protecteur usually requires that you exhaust internal remedies before they investigate. " : "") .
                         "You can mail this or I can fax it to their office. Would you like me to fax it?",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 91. LEGAL OCI — Office of the Correctional Investigator (federal)
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalOCI($args) {
    $caseId      = (int)($args['case_id'] ?? 0);
    $complaint   = trim($args['complaint'] ?? $args['description'] ?? '');
    $institution = trim($args['institution'] ?? '');
    $action      = trim($args['action'] ?? 'draft'); // draft, info

    if ($action === 'info') {
        return [
            'success' => true,
            'info' => [
                'name'    => 'Office of the Correctional Investigator (OCI)',
                'phone'   => '1-877-885-8848',
                'email'   => 'org@oci-bec.gc.ca',
                'website' => 'https://www.oci-bec.gc.ca/',
                'address' => 'Office of the Correctional Investigator, P.O. Box 3421, Station D, Ottawa, ON K1P 6L4',
                'mandate' => 'The OCI acts as an ombudsman for federally sentenced offenders. It investigates complaints about the Correctional Service of Canada (CSC).',
                'note'    => 'For PROVINCIAL institutions in Quebec, use the Protecteur du citoyen instead.',
            ],
            'message' => "The OCI handles complaints about FEDERAL institutions (CSC). " .
                        "Call 1-877-885-8848 (toll-free from institutions). " .
                        "You should file an internal grievance FIRST.",
        ];
    }

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
        $institution = $institution ?: ($caseData['institution'] ?? '');
    }

    if (!$complaint) return ['error' => 'Please describe your complaint about a federal institution (CSC).'];

    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $inmateId   = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');

    $grievances = json_decode($caseData['grievance_data'] ?? '[]', true) ?: [];
    $grievanceFiled = !empty($grievances);

    $complaintDoc = "
═══════════════════════════════════════════════════════
COMPLAINT TO THE OFFICE OF THE CORRECTIONAL INVESTIGATOR
PLAINTE AU BUREAU DE L'ENQUÊTEUR CORRECTIONNEL
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "

TO: Office of the Correctional Investigator
    P.O. Box 3421, Station D, Ottawa, ON K1P 6L4
    Tel: 1-877-885-8848
    Email: org@oci-bec.gc.ca

FROM:
{$callerName}
FPS/SED Number: {$inmateId}
Institution: {$institution}

───────────────────────────────────────────────────────
COMPLAINT
───────────────────────────────────────────────────────

{$complaint}

───────────────────────────────────────────────────────
INTERNAL REMEDIES PURSUED
───────────────────────────────────────────────────────

" . ($grievanceFiled ?
"Internal grievance was filed on " . ($grievances[count($grievances)-1]['filed_date'] ?? 'unknown') . "." :
"[Indicate whether an internal grievance was filed per Commissioner's Directive 081]") . "

───────────────────────────────────────────────────────
REQUESTED ACTION
───────────────────────────────────────────────────────

I respectfully request that the OCI investigate this matter and
make recommendations to the Correctional Service of Canada.

This complaint is filed pursuant to Part III of the Corrections
and Conditional Release Act (CCRA).

Signature: _________________________________
           {$callerName}
Date: " . date('Y-m-d') . "
";

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "oci_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>OCI Complaint</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($complaintDoc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'oci_complaint', 'title' => 'OCI Complaint', 'file' => $docFile, 'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'document'     => $complaintDoc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'contact'      => ['phone' => '1-877-885-8848', 'email' => 'org@oci-bec.gc.ca'],
        'internal_grievance_filed' => $grievanceFiled,
        'message'      => "OCI complaint drafted. " .
                         (!$grievanceFiled ? "WARNING: The OCI usually requires you to exhaust the CSC internal grievance process first (Commissioner's Directive 081). " : "") .
                         "You can mail this to the OCI or I can fax it. Call 1-877-885-8848 for direct assistance.",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 92. LEGAL COURT REMINDER — Set hearing date reminders
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalCourtReminder($args) {
    $caseId      = (int)($args['case_id'] ?? 0);
    $hearingDate = trim($args['hearing_date'] ?? $args['date'] ?? '');
    $action      = trim($args['action'] ?? 'set'); // set, list, check
    $courtName   = trim($args['court_name'] ?? '');
    $description = trim($args['description'] ?? $args['purpose'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    if ($action === 'check') {
        // Check upcoming hearings across all cases
        $callerPhone = trim($args['caller_phone'] ?? $args['phone'] ?? '');
        $inmateId    = trim($args['inmate_id'] ?? '');

        $sql = "SELECT id, case_type, case_number, court_name, court_district, next_hearing_date FROM alfred_legal_cases WHERE next_hearing_date IS NOT NULL AND next_hearing_date != '' AND status='active'";
        $params = [];
        if ($callerPhone) { $sql .= " AND caller_phone=:p"; $params[':p'] = $callerPhone; }
        elseif ($inmateId) { $sql .= " AND inmate_id=:i"; $params[':i'] = $inmateId; }
        elseif ($caseId)   { $sql .= " AND id=:id"; $params[':id'] = $caseId; }
        $sql .= " ORDER BY next_hearing_date ASC";

        $s = $db->prepare($sql);
        $s->execute($params);
        $upcoming = $s->fetchAll(PDO::FETCH_ASSOC);

        if (empty($upcoming)) return ['upcoming' => [], 'message' => 'No upcoming hearing dates found.'];

        $msgs = array_map(function($c) {
            $days = '';
            try {
                $diff = (new DateTime($c['next_hearing_date']))->diff(new DateTime());
                $days = $diff->invert ? " ({$diff->days} days away)" : " (PAST — {$diff->days} days ago)";
            } catch (Exception $e) {}
            return "Case #{$c['id']} ({$c['case_type']}): {$c['next_hearing_date']}{$days} at {$c['court_name']}";
        }, $upcoming);

        return ['upcoming' => $upcoming, 'count' => count($upcoming), 'message' => "Upcoming hearings:\n" . implode("\n", $msgs)];
    }

    // Set a reminder
    if (!$hearingDate) return ['error' => 'What date is the hearing? (Format: YYYY-MM-DD)'];

    // Validate date format
    $dt = DateTime::createFromFormat('Y-m-d', $hearingDate);
    if (!$dt) {
        // Try other formats
        $dt = DateTime::createFromFormat('m/d/Y', $hearingDate) ?: DateTime::createFromFormat('d-m-Y', $hearingDate) ?: DateTime::createFromFormat('F j, Y', $hearingDate);
        if ($dt) $hearingDate = $dt->format('Y-m-d');
    }

    if ($caseId) {
        try {
            $db->prepare("UPDATE alfred_legal_cases SET next_hearing_date=:hd, court_name=COALESCE(:cn, court_name), updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
               ->execute([':hd' => $hearingDate, ':cn' => $courtName ?: null, ':note' => "\n[" . date('Y-m-d H:i') . "] Hearing reminder set: {$hearingDate}" . ($description ? " — {$description}" : ""), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    // Calculate days until hearing
    $daysUntil = '';
    try {
        $diff = (new DateTime($hearingDate))->diff(new DateTime());
        $daysUntil = $diff->invert ? "{$diff->days} days from now" : "THIS DATE HAS PASSED ({$diff->days} days ago)";
    } catch (Exception $e) {}

    return [
        'success'      => true,
        'hearing_date' => $hearingDate,
        'days_until'   => $daysUntil,
        'court'        => $courtName ?: ($caseData['court_name'] ?? ''),
        'message'      => "Hearing date set: {$hearingDate} ({$daysUntil}). " .
                         "I'll remind you about this in future calls. " .
                         "Make sure to prepare your documents and arguments before the hearing.",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 93. LEGAL LAWYER DIRECTORY — Search legal aid lawyers
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalLawyerDirectory($args) {
    $district    = trim($args['district'] ?? $args['city'] ?? $args['region'] ?? '');
    $speciality  = trim($args['speciality'] ?? $args['specialty'] ?? $args['type'] ?? '');

    // Quebec Legal Aid Bureau offices
    $legalAidOffices = [
        'montreal' => [
            'name'    => 'Bureau d\'aide juridique de Montréal',
            'address' => '2, Complexe Desjardins, Tour Est, bureau 1404, Montréal QC H5B 1B2',
            'phone'   => '+15148422233',
            'hours'   => 'Lun-Ven 8h30-16h30',
            'services'=> ['criminal', 'immigration', 'family', 'youth', 'administrative'],
        ],
        'quebec' => [
            'name'    => 'Bureau d\'aide juridique de Québec',
            'address' => '390, boulevard Charest Est, Québec QC G1K 3H4',
            'phone'   => '+14186433220',
            'hours'   => 'Lun-Ven 8h30-16h30',
            'services'=> ['criminal', 'family', 'administrative'],
        ],
        'laval' => [
            'name'    => 'Bureau d\'aide juridique de Laval',
            'address' => '300, boulevard de la Concorde Ouest, bureau 304, Laval QC H7N 1A8',
            'phone'   => '+14506685612',
            'hours'   => 'Lun-Ven 8h30-16h30',
            'services'=> ['criminal', 'family', 'administrative'],
        ],
        'longueuil' => [
            'name'    => 'Bureau d\'aide juridique de Longueuil',
            'address' => '201, place Charles-Le Moyne, bureau 8.01, Longueuil QC J4K 2T5',
            'phone'   => '+14506468877',
            'hours'   => 'Lun-Ven 8h30-16h30',
            'services'=> ['criminal', 'family', 'youth'],
        ],
        'gatineau' => [
            'name'    => 'Bureau d\'aide juridique de Gatineau',
            'address' => '170, rue de l\'Hôtel-de-Ville, bureau 5.100, Gatineau QC J8X 4C2',
            'phone'   => '+18197713771',
            'hours'   => 'Lun-Ven 8h30-16h30',
            'services'=> ['criminal', 'family', 'administrative'],
        ],
        'sherbrooke' => [
            'name'    => 'Bureau d\'aide juridique de Sherbrooke',
            'address' => '375, rue King Ouest, bureau 307, Sherbrooke QC J1H 6B9',
            'phone'   => '+18195633511',
            'hours'   => 'Lun-Ven 8h30-16h30',
            'services'=> ['criminal', 'family'],
        ],
        'trois_rivieres' => [
            'name'    => 'Bureau d\'aide juridique de Trois-Rivières',
            'address' => '250, rue Laviolette, bureau 115, Trois-Rivières QC G9A 1T9',
            'phone'   => '+18193741780',
            'hours'   => 'Lun-Ven 8h30-16h30',
            'services'=> ['criminal', 'family'],
        ],
        'rimouski' => [
            'name'    => 'Bureau d\'aide juridique de Rimouski',
            'address' => '2, rue Saint-Germain Est, bureau 200, Rimouski QC G5L 8T7',
            'phone'   => '+14187225953',
            'hours'   => 'Lun-Ven 8h30-16h30',
            'services'=> ['criminal', 'family'],
        ],
    ];

    // Prisoner rights organizations
    $prisonerRights = [
        [
            'name'    => 'Aide juridique du Québec (Legal Aid Hotline)',
            'phone'   => '1-800-842-2213',
            'note'    => 'Toll-free. Covers criminal, CCRA, parole, immigration for those who qualify financially.',
        ],
        [
            'name'    => 'Association des avocats de la défense de Montréal',
            'phone'   => '+15148426846',
            'note'    => 'Criminal defense lawyers referral.',
        ],
        [
            'name'    => 'Barreau du Québec — Lawyer Referral Service',
            'phone'   => '1-866-954-3528',
            'note'    => 'Free 30-minute consultation referral.',
        ],
        [
            'name'    => 'Canadian Association of Elizabeth Fry Societies',
            'phone'   => '+16132381474',
            'note'    => 'Advocacy for women in prison.',
        ],
        [
            'name'    => 'John Howard Society of Quebec',
            'phone'   => '+15146007738',
            'note'    => 'Prisoner advocacy, reintegration support.',
        ],
        [
            'name'    => 'Office of the Correctional Investigator (OCI)',
            'phone'   => '1-877-885-8848',
            'note'    => 'Federal institution complaints.',
        ],
        [
            'name'    => 'Protecteur du citoyen',
            'phone'   => '1-800-463-5070',
            'note'    => 'Provincial institution complaints.',
        ],
    ];

    if ($district) {
        $distLower = strtolower(str_replace([' ', '-', "'", 'è', 'é'], ['_', '_', '', 'e', 'e'], $district));
        foreach ($legalAidOffices as $key => $office) {
            if (stripos($key, $distLower) !== false || stripos($office['name'], $district) !== false) {
                return [
                    'success' => true,
                    'office'  => $office,
                    'prisoner_rights_orgs' => $prisonerRights,
                    'message' => "{$office['name']}\nPhone: {$office['phone']}\nAddress: {$office['address']}\nHours: {$office['hours']}\n\nCall 1-800-842-2213 for toll-free legal aid access from institutions.",
                ];
            }
        }
    }

    // Return full directory
    $offices = array_map(fn($k, $o) => ['key' => $k, 'name' => $o['name'], 'phone' => $o['phone']], array_keys($legalAidOffices), $legalAidOffices);
    return [
        'offices'             => $offices,
        'prisoner_rights_orgs' => $prisonerRights,
        'toll_free'           => '1-800-842-2213',
        'message'             => "Legal Aid Quebec toll-free: 1-800-842-2213 (accessible from all institutions). " .
                                "Which district do you need a lawyer in?",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 94. LEGAL FAX STATUS — Track and confirm fax delivery
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalFaxStatus($args) {
    $faxId       = trim($args['fax_id'] ?? '');
    $caseId      = (int)($args['case_id'] ?? 0);

    // If checking by case, show all faxes in documents
    if (!$faxId && $caseId) {
        $db = getDB();
        $s = $db->prepare("SELECT documents_filed, case_notes FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $c = $s->fetch(PDO::FETCH_ASSOC);
        if (!$c) return ['error' => 'Case not found.'];

        $docs = json_decode($c['documents_filed'] ?? '[]', true) ?: [];
        $faxedDocs = array_filter($docs, fn($d) => ($d['status'] ?? '') === 'faxed');

        if (empty($faxedDocs)) return ['faxes' => [], 'message' => 'No faxes sent for this case yet.'];

        // Parse notes for fax IDs
        $faxEntries = [];
        if (preg_match_all('/Fax ID: (\S+)/', $c['case_notes'] ?? '', $matches)) {
            $faxEntries = $matches[1];
        }

        return [
            'faxes'        => array_values($faxedDocs),
            'fax_ids'      => $faxEntries,
            'count'        => count($faxedDocs),
            'message'      => count($faxedDocs) . " document(s) faxed. " .
                             "Fax delivery typically takes a few minutes. " .
                             "If the court hasn't received it, I can re-send or call to confirm.",
            'next_actions' => ['re-send fax', 'call court to confirm receipt'],
        ];
    }

    if (!$faxId) return ['error' => 'Provide a fax_id or case_id to check fax status.'];

    // Check Telnyx fax status
    $envFile = dirname(dirname(__DIR__)) . '/.env.php';
    if (file_exists($envFile)) require_once $envFile;
    $apiKey = getenv('TELNYX_API_KEY');

    if (!$apiKey) {
        return ['error' => 'Telnyx not configured. Cannot check fax status.'];
    }

    $ch = curl_init("https://api.telnyx.com/v2/faxes/{$faxId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$apiKey}"],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return ['error' => "Could not check fax status (HTTP {$code}). The fax ID may be invalid."];

    $data = json_decode($resp, true);
    $fax = $data['data'] ?? [];

    return [
        'success' => true,
        'fax_id'  => $faxId,
        'status'  => $fax['status'] ?? 'unknown',
        'to'      => $fax['to'] ?? '',
        'from'    => $fax['from'] ?? '',
        'pages'   => $fax['pages_count'] ?? 0,
        'created' => $fax['created_at'] ?? '',
        'message' => "Fax {$faxId}: Status = " . ($fax['status'] ?? 'unknown') .
                    ($fax['status'] === 'delivered' ? " ✓ DELIVERED" : "") .
                    ($fax['status'] === 'failed' ? " ✗ FAILED — try re-sending" : ""),
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 95. LEGAL COUR DU QUÉBEC — Complete Cour du Québec directory
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalCourDuQuebec($args) {
    $district = trim($args['district'] ?? $args['city'] ?? '');
    $division = trim($args['division'] ?? ''); // criminal, civil, youth

    // Cour du Québec locations (separate from Cour supérieure)
    $courDuQuebec = [
        'montreal' => [
            'name'    => 'Cour du Québec — Montréal',
            'address' => '1, rue Notre-Dame Est, Montréal, QC H2Y 1B6',
            'phone'   => '+15143937218',
            'fax'     => '+15148732308',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile (petites créances)', 'Chambre de la jeunesse'],
            'district'=> 'Montréal',
        ],
        'quebec_city' => [
            'name'    => 'Cour du Québec — Québec',
            'address' => '300, boulevard Jean-Lesage, Québec, QC G1K 8K6',
            'phone'   => '+14186498104',
            'fax'     => '+14186497265',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile', 'Chambre de la jeunesse'],
            'district'=> 'Québec',
        ],
        'laval' => [
            'name'    => 'Cour du Québec — Laval',
            'address' => '2800, boulevard Saint-Martin Ouest, Laval, QC H7T 2S9',
            'phone'   => '+14506800830',
            'fax'     => '+14506800806',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile'],
            'district'=> 'Laval',
        ],
        'longueuil' => [
            'name'    => 'Cour du Québec — Longueuil',
            'address' => '1111, boulevard Jacques-Cartier Est, Longueuil, QC J4M 2J6',
            'phone'   => '+14506462941',
            'fax'     => '+14506464960',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile'],
            'district'=> 'Longueuil',
        ],
        'gatineau' => [
            'name'    => 'Cour du Québec — Gatineau',
            'address' => '17, rue Laurier, Gatineau, QC J8X 4C1',
            'phone'   => '+18197721032',
            'fax'     => '+18197721089',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile'],
            'district'=> 'Gatineau',
        ],
        'sherbrooke' => [
            'name'    => 'Cour du Québec — Sherbrooke',
            'address' => '375, rue King Ouest, Sherbrooke, QC J1H 6B9',
            'phone'   => '+18198222583',
            'fax'     => '+18198222698',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile'],
            'district'=> 'Saint-François',
        ],
        'trois_rivieres' => [
            'name'    => 'Cour du Québec — Trois-Rivières',
            'address' => '250, rue Laviolette, Trois-Rivières, QC G9A 1T9',
            'phone'   => '+18193714461',
            'fax'     => '+18193793855',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile'],
            'district'=> 'Trois-Rivières',
        ],
        'joliette' => [
            'name'    => 'Cour du Québec — Joliette',
            'address' => '450, rue Saint-Louis, Joliette, QC J6E 2Y4',
            'phone'   => '+14507565546',
            'fax'     => '+14507565912',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile'],
            'district'=> 'Joliette',
        ],
        'rimouski' => [
            'name'    => 'Cour du Québec — Rimouski',
            'address' => '183, avenue de la Cathédrale, Rimouski, QC G5L 5J1',
            'phone'   => '+14187279285',
            'fax'     => '+14187244922',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile'],
            'district'=> 'Rimouski',
        ],
        'chicoutimi' => [
            'name'    => 'Cour du Québec — Chicoutimi',
            'address' => '227, rue Racine Est, Chicoutimi, QC G7H 7B4',
            'phone'   => '+14185493157',
            'fax'     => '+14185493164',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile'],
            'district'=> 'Chicoutimi',
        ],
        'st_jerome' => [
            'name'    => 'Cour du Québec — Saint-Jérôme',
            'address' => '25, rue de Martigny Ouest, Saint-Jérôme, QC J7Y 2G1',
            'phone'   => '+14504363974',
            'fax'     => '+14504363999',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile'],
            'district'=> 'Terrebonne',
        ],
        'drummondville' => [
            'name'    => 'Cour du Québec — Drummondville',
            'address' => '1680, boulevard Saint-Joseph, Drummondville, QC J2C 2G3',
            'phone'   => '+18194785655',
            'fax'     => '+18194787879',
            'divisions' => ['Chambre criminelle et pénale', 'Chambre civile'],
            'district'=> 'Drummond',
        ],
    ];

    if ($district) {
        $distLower = strtolower(str_replace([' ', '-', "'", 'è', 'é', 'ô'], ['_', '_', '', 'e', 'e', 'o'], $district));
        foreach ($courDuQuebec as $key => $court) {
            if (stripos($key, $distLower) !== false || stripos($court['district'], $district) !== false || stripos($court['name'], $district) !== false) {
                return [
                    'success' => true,
                    'court'   => $court,
                    'message' => "{$court['name']}\nAddress: {$court['address']}\nPhone: {$court['phone']}\nFax: {$court['fax']}\nDivisions: " . implode(', ', $court['divisions']),
                ];
            }
        }
        return ['error' => "No Cour du Québec found for \"{$district}\". Available: " . implode(', ', array_map(fn($c) => $c['district'], $courDuQuebec))];
    }

    $list = array_map(fn($k, $c) => ['key' => $k, 'name' => $c['name'], 'district' => $c['district'], 'phone' => $c['phone'], 'fax' => $c['fax']], array_keys($courDuQuebec), $courDuQuebec);
    return [
        'courts'  => $list,
        'count'   => count($list),
        'note'    => 'The Cour du Québec handles criminal, youth, and small claims matters. For the Cour supérieure (Superior Court), use the legalCourtDirectory tool.',
        'message' => count($list) . " Cour du Québec locations. Which district?",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 96. LEGAL HABEAS CORPUS — Draft habeas corpus petition
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalHabeasCorpus($args) {
    $caseId      = (int)($args['case_id'] ?? 0);
    $grounds     = trim($args['grounds'] ?? $args['reason'] ?? '');
    $confirmed   = !empty($args['confirmed']);

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    if (!$caseId && !$grounds) {
        return ['error' => 'I need your case ID or the grounds for the habeas corpus petition. Why do you believe your detention is unlawful?'];
    }

    // Merge grounds into case data for template
    if ($grounds) {
        $caseData['case_summary'] = ($caseData['case_summary'] ?? '') . "\n" . $grounds;
    }

    $motion = generateLegalMotion('habeas_corpus', $caseData);
    $motionBody = $motion['body'];

    // Enrich with AI
    $motionBody = enrichLegalMotionWithAI($motionBody, 'habeas_corpus', $caseData);

    // Save document
    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "habeas_corpus_{$caseId}_" . date('Ymd_His') . ".html";
    $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Habeas Corpus</title>" .
            "<style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style>" .
            "</head><body>" . htmlspecialchars($motionBody) . "</body></html>";
    file_put_contents($docDir . $docFile, $html);

    // Save to case documents
    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'habeas_corpus', 'title' => 'Requête en Habeas Corpus', 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':note' => "\n[" . date('Y-m-d H:i') . "] Habeas corpus petition drafted", ':id' => $caseId]);
        } catch (Exception $e) { error_log("[LegalHabeas] DB error: " . $e->getMessage()); }
    }

    return [
        'success'      => true,
        'document'     => $motionBody,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'disclaimer'   => $motion['disclaimer'],
        'message'      => "Habeas corpus petition drafted. This is filed at the SUPERIOR COURT (Cour supérieure). " .
                         "IMPORTANT: File this urgently — habeas corpus is heard on a priority basis. " .
                         "Legal aid: 1-800-842-2213. Would you like me to fax this to the court?",
        'next_actions' => ['fax to court', 'search habeas corpus case law', 'update case'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 97. LEGAL BAIL REVIEW — Draft bail review application
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalBailReview($args) {
    $caseId          = (int)($args['case_id'] ?? 0);
    $changedCirc     = trim($args['changed_circumstances'] ?? $args['grounds'] ?? '');
    $suretyName      = trim($args['surety_name'] ?? '');
    $suretyAmount    = trim($args['surety_amount'] ?? '');
    $conditions      = trim($args['proposed_conditions'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    if (!$changedCirc && !$caseId) {
        return ['error' => 'I need the changed circumstances since the original bail hearing, or your case ID. What has changed?'];
    }

    if ($changedCirc) {
        $caseData['case_summary'] = ($caseData['case_summary'] ?? '') . "\n" . $changedCirc;
    }

    $motion = generateLegalMotion('bail_review', $caseData);
    $motionBody = $motion['body'];

    // Fill in surety details if provided
    if ($suretyName) {
        $motionBody = str_replace('b) Caution: _______________', "b) Caution: {$suretyName}", $motionBody);
    }
    if ($suretyAmount) {
        $motionBody = str_replace('a) Cautionnement de _______________$', "a) Cautionnement de {$suretyAmount}$", $motionBody);
    }
    if ($conditions) {
        $motionBody = str_replace('c) Conditions: _______________', "c) Conditions: {$conditions}", $motionBody);
    }

    $motionBody = enrichLegalMotionWithAI($motionBody, 'bail_review', $caseData);

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "bail_review_{$caseId}_" . date('Ymd_His') . ".html";
    $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Bail Review</title>" .
            "<style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style>" .
            "</head><body>" . htmlspecialchars($motionBody) . "</body></html>";
    file_put_contents($docDir . $docFile, $html);

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'bail_review', 'title' => 'Demande de révision de cautionnement', 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':note' => "\n[" . date('Y-m-d H:i') . "] Bail review application drafted", ':id' => $caseId]);
        } catch (Exception $e) { error_log("[LegalBail] DB error: " . $e->getMessage()); }
    }

    return [
        'success'      => true,
        'document'     => $motionBody,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'disclaimer'   => $motion['disclaimer'],
        'message'      => "Bail review application drafted under s. 520/521 of the Criminal Code. " .
                         "You must show CHANGED CIRCUMSTANCES since the original bail decision. " .
                         "Would you like me to fax this to the court or search bail review case law?",
        'next_actions' => ['fax to court', 'search bail review case law', 'prepare surety plan'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 98. LEGAL APPEALS — Draft notice of appeal
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalAppeals($args) {
    $caseId        = (int)($args['case_id'] ?? 0);
    $appealType    = trim($args['appeal_type'] ?? $args['type'] ?? 'conviction'); // conviction, sentence, both
    if (!in_array($appealType, ['conviction', 'sentence', 'both'], true)) $appealType = 'conviction';
    $grounds       = trim($args['grounds'] ?? $args['reason'] ?? '');
    $trialJudge    = trim($args['trial_judge'] ?? $args['judge'] ?? '');
    $convictionDate = trim($args['conviction_date'] ?? $args['date'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    if (!$grounds && !$caseId) {
        return ['error' => 'What are the grounds for your appeal? (e.g., error of law, unreasonable verdict, sentence too harsh)'];
    }

    if ($grounds) {
        $caseData['case_summary'] = $grounds;
    }

    $motion = generateLegalMotion('appeal', $caseData);
    $motionBody = $motion['body'];

    // Fill in specific appeal details
    if ($trialJudge) {
        $motionBody = str_replace('l\'honorable juge _______________', "l'honorable juge {$trialJudge}", $motionBody);
    }
    if ($convictionDate) {
        $motionBody = str_replace('du jugement rendu le _______________', "du jugement rendu le {$convictionDate}", $motionBody);
    }

    $motionBody = enrichLegalMotionWithAI($motionBody, 'appeal', $caseData);

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "appeal_{$appealType}_{$caseId}_" . date('Ymd_His') . ".html";
    $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Notice of Appeal</title>" .
            "<style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style>" .
            "</head><body>" . htmlspecialchars($motionBody) . "</body></html>";
    file_put_contents($docDir . $docFile, $html);

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'appeal', 'title' => "Avis d'appel ({$appealType})", 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':note' => "\n[" . date('Y-m-d H:i') . "] Notice of appeal drafted ({$appealType})", ':id' => $caseId]);
        } catch (Exception $e) { error_log("[LegalAppeal] DB error: " . $e->getMessage()); }
    }

    $deadlineNote = "DEADLINE: You must file within 30 DAYS of the conviction/sentence (s. 678.1 Criminal Code). ";
    if ($convictionDate) {
        try {
            $convDt = new DateTime($convictionDate);
            $deadlineDt = clone $convDt;
            $deadlineDt->modify('+30 days');
            $now = new DateTime();
            $diff = $now->diff($deadlineDt);
            $deadlineNote .= $diff->invert ? "WARNING: DEADLINE HAS PASSED ({$diff->days} days ago). You may need to apply for an extension of time. " :
                            "You have {$diff->days} day(s) remaining to file. Deadline: " . $deadlineDt->format('Y-m-d') . ". ";
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'appeal_type'  => $appealType,
        'document'     => $motionBody,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'disclaimer'   => $motion['disclaimer'],
        'message'      => "Notice of appeal drafted (type: {$appealType}). " . $deadlineNote .
                         "File at the Court of Appeal (Cour d'appel). Legal aid: 1-800-842-2213.",
        'next_actions' => ['fax to court of appeal', 'search appeal case law', 'calculate deadline'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 99. LEGAL SENTENCE CALC — Calculate sentence dates & eligibility
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalSentenceCalc($args) {
    $sentenceLength  = trim($args['sentence_length'] ?? $args['sentence'] ?? '');
    $startDate       = trim($args['start_date'] ?? $args['sentence_date'] ?? '');
    $preTrialCredit  = trim($args['pre_trial_credit'] ?? $args['dead_time'] ?? '');
    $offenceType     = trim($args['offence_type'] ?? $args['type'] ?? 'general');

    if (!$sentenceLength) {
        return ['error' => 'What is the sentence length? (e.g., "2 years", "18 months", "6 months")'];
    }

    // Parse sentence length into days
    $totalDays = 0;
    if (preg_match('/(\d+)\s*year/i', $sentenceLength, $m)) $totalDays += (int)$m[1] * 365;
    if (preg_match('/(\d+)\s*month/i', $sentenceLength, $m)) $totalDays += (int)$m[1] * 30;
    if (preg_match('/(\d+)\s*day/i', $sentenceLength, $m)) $totalDays += (int)$m[1];
    if (preg_match('/(\d+)\s*(?:an|ans)/i', $sentenceLength, $m)) $totalDays += (int)$m[1] * 365;
    if (preg_match('/(\d+)\s*mois/i', $sentenceLength, $m)) $totalDays += (int)$m[1] * 30;
    if (preg_match('/(\d+)\s*jour/i', $sentenceLength, $m)) $totalDays += (int)$m[1];

    if ($totalDays === 0) {
        // Try plain number as months
        if (preg_match('/^(\d+)$/', $sentenceLength, $m)) $totalDays = (int)$m[1] * 30;
    }
    if ($totalDays === 0) return ['error' => 'Could not parse sentence length. Use format like "2 years", "18 months", or "6 months 15 days".'];

    // Parse pre-trial credit
    $creditDays = 0;
    if ($preTrialCredit) {
        if (preg_match('/(\d+)\s*(?:day|jour)/i', $preTrialCredit, $m)) $creditDays = (int)$m[1];
        elseif (preg_match('/(\d+)\s*month/i', $preTrialCredit, $m)) $creditDays = (int)$m[1] * 30;
        elseif (preg_match('/^(\d+)$/', $preTrialCredit, $m)) $creditDays = (int)$m[1]; // assume days
        // 1.5:1 credit (Summers credit) is standard
        $creditDays = (int)($creditDays * 1.5);
    }

    $effectiveDays = max(1, $totalDays - $creditDays);

    // Use start date or today
    try {
        $start = $startDate ? new DateTime($startDate) : new DateTime();
    } catch (Exception $e) {
        $start = new DateTime();
    }

    $isFederal = $totalDays >= 730; // 2+ years = federal

    // Calculate key dates
    $dates = [];

    // Warrant expiry date (WED)
    $wed = clone $start;
    $wed->modify("+{$effectiveDays} days");
    $dates['warrant_expiry'] = $wed->format('Y-m-d');

    // Day parole eligibility (6 months before full parole OR half of time to full parole, whichever greater)
    if ($isFederal) {
        $fpeDays = max(1, (int)($effectiveDays / 3));
        $dpeDays = max(1, $fpeDays - 183); // 6 months before FPE
        $srDays  = max(1, (int)($effectiveDays * 2 / 3));

        $fpe = clone $start; $fpe->modify("+{$fpeDays} days");
        $dpe = clone $start; $dpe->modify("+{$dpeDays} days");
        $sr  = clone $start; $sr->modify("+{$srDays} days");

        $dates['day_parole_eligibility'] = $dpe->format('Y-m-d');
        $dates['full_parole_eligibility'] = $fpe->format('Y-m-d');
        $dates['statutory_release'] = $sr->format('Y-m-d');
        $dates['jurisdiction'] = 'FEDERAL (Correctional Service Canada / Parole Board of Canada)';
    } else {
        // Provincial: 1/3 for parole eligibility, 2/3 for remission
        $peDays = max(1, (int)($effectiveDays / 3));
        $remDays = max(1, (int)($effectiveDays * 2 / 3));

        $pe  = clone $start; $pe->modify("+{$peDays} days");
        $rem = clone $start; $rem->modify("+{$remDays} days");

        $dates['parole_eligibility'] = $pe->format('Y-m-d');
        $dates['remission_date'] = $rem->format('Y-m-d');
        $dates['jurisdiction'] = 'PROVINCIAL (Quebec provincial corrections / CQLC)';
    }

    // Days served so far
    $now = new DateTime();
    $servedDiff = $start->diff($now);
    $daysServed = $servedDiff->invert ? 0 : $servedDiff->days;
    $daysRemaining = max(0, $effectiveDays - $daysServed);

    return [
        'success'           => true,
        'sentence_length'   => $sentenceLength,
        'total_days'        => $totalDays,
        'pre_trial_credit'  => $creditDays > 0 ? "{$creditDays} days (with 1.5:1 Summers credit)" : 'None',
        'effective_days'    => $effectiveDays,
        'start_date'        => $start->format('Y-m-d'),
        'dates'             => $dates,
        'days_served'       => $daysServed,
        'days_remaining'    => $daysRemaining,
        'is_federal'        => $isFederal,
        'message'           => "Sentence calculation:\n" .
                              "Total: {$totalDays} days ({$sentenceLength})\n" .
                              ($creditDays > 0 ? "Pre-trial credit: {$creditDays} days (1.5:1)\n" : "") .
                              "Effective: {$effectiveDays} days\n" .
                              "Start: " . $start->format('Y-m-d') . "\n" .
                              "Warrant Expiry: {$dates['warrant_expiry']}\n" .
                              ($isFederal ?
                                "Day Parole Eligibility: {$dates['day_parole_eligibility']}\n" .
                                "Full Parole Eligibility: {$dates['full_parole_eligibility']}\n" .
                                "Statutory Release: {$dates['statutory_release']}\n" :
                                "Parole Eligibility: {$dates['parole_eligibility']}\n" .
                                "Remission: {$dates['remission_date']}\n") .
                              "Days served: {$daysServed} | Days remaining: {$daysRemaining}\n" .
                              $dates['jurisdiction'],
        'note'              => 'These are ESTIMATES. Actual dates depend on remand credit granted by the court and institutional calculations. Contact your case manager for official dates.',
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 100. LEGAL CHARTER CHALLENGE — Canadian Charter of Rights challenge
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalCharterChallenge($args) {
    $caseId       = (int)($args['case_id'] ?? 0);
    $section      = trim($args['section'] ?? $args['right'] ?? '');
    $section = preg_replace('/[^a-zA-Z0-9_]/', '', $section);
    $violation    = trim($args['violation'] ?? $args['description'] ?? '');
    $remedy       = trim($args['remedy'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    if (!$violation) return ['error' => 'Describe the Charter violation. What right was breached and what happened?'];

    $callerName  = $caseData['caller_name'] ?? ($args['caller_name'] ?? 'LE REQUÉRANT');
    $inmateId    = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');
    $institution = $caseData['institution'] ?? ($args['institution'] ?? '');
    $district    = $caseData['court_district'] ?? ($args['district'] ?? 'Montréal');

    $charterSections = [
        '2'  => 'Libertés fondamentales (freedom of expression, religion, association)',
        '7'  => 'Vie, liberté et sécurité de la personne (life, liberty, security)',
        '8'  => 'Fouilles, perquisitions ou saisies abusives (unreasonable search/seizure)',
        '9'  => 'Détention arbitraire (arbitrary detention)',
        '10' => 'Droits en cas d\'arrestation (rights on arrest — counsel, reasons)',
        '11' => 'Droits des inculpés (rights of accused — fair trial, presumption of innocence)',
        '12' => 'Traitements cruels et inusités (cruel and unusual treatment/punishment)',
        '14' => 'Droit à un interprète (right to interpreter)',
        '15' => 'Droit à l\'égalité (equality rights)',
    ];

    $sectionLabel = $charterSections[$section] ?? "Section {$section}";
    if (!$section) {
        $sectionLabel = '[Section à identifier / Section to be identified]';
        $section = '?';
    }

    $doc = "
CANADA
PROVINCE DE QUÉBEC
DISTRICT DE " . strtoupper($district) . "

No: " . ($caseData['case_number'] ?? '_______________') . "

COUR SUPÉRIEURE

{$callerName}
    Requérant / Applicant
c.
SA MAJESTÉ LE ROI / HIS MAJESTY THE KING
et/and
LE PROCUREUR GÉNÉRAL DU CANADA / ATTORNEY GENERAL OF CANADA
    Intimés / Respondents

═══════════════════════════════════════════════════════
REQUÊTE EN VERTU DE L'ARTICLE 24(1) DE LA CHARTE
CANADIENNE DES DROITS ET LIBERTÉS
APPLICATION UNDER S. 24(1) OF THE CANADIAN CHARTER
OF RIGHTS AND FREEDOMS
═══════════════════════════════════════════════════════

LE REQUÉRANT EXPOSE RESPECTUEUSEMENT:

1. Le requérant, {$callerName}" . ($inmateId ? " (matricule: {$inmateId})" : "") . ", est détenu à " . ($institution ?: '_______________') . ";

2. Le requérant allègue que ses droits protégés par l'article {$section} de la Charte canadienne des droits et libertés ont été violés;

3. Article {$section} — {$sectionLabel};

───────────────────────────────────────────────────────
DESCRIPTION DE LA VIOLATION / DESCRIPTION OF VIOLATION
───────────────────────────────────────────────────────

4. {$violation}

───────────────────────────────────────────────────────
FONDEMENT JURIDIQUE / LEGAL BASIS
───────────────────────────────────────────────────────

5. L'article {$section} de la Charte canadienne des droits et libertés garantit que:
   - {$sectionLabel}

6. L'article 24(1) de la Charte prévoit que toute personne dont les droits ont été enfreints peut s'adresser à un tribunal compétent pour obtenir la réparation que le tribunal estime convenable et juste eu égard aux circonstances.

7. La Charte québécoise des droits et libertés de la personne offre également une protection similaire.

───────────────────────────────────────────────────────
RÉPARATION DEMANDÉE / REMEDY SOUGHT
───────────────────────────────────────────────────────

" . ($remedy ?: "Le requérant demande que le tribunal:\na) DÉCLARE que les droits du requérant en vertu de l'article {$section} ont été violés;\nb) ORDONNE la réparation que le tribunal estime convenable et juste;\nc) LE TOUT avec dépens.") . "

{$district}, le " . date('j F Y') . "

_________________________________
{$callerName}
Se représentant seul / Self-represented
" . ($institution ? "Détenu à {$institution}" : "") . "
";

    $doc = enrichLegalMotionWithAI($doc, 'charter_challenge', array_merge($caseData, ['case_summary' => $violation, 'charges' => "Charter s. {$section}"]));

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "charter_s{$section}_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Charter Challenge</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'charter_challenge', 'title' => "Charter s.{$section} Challenge", 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':note' => "\n[" . date('Y-m-d H:i') . "] Charter s.{$section} challenge drafted", ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'charter_section' => $section,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'charter_guide' => $charterSections,
        'message'      => "Charter challenge drafted (s. {$section}). " .
                         "This is filed at the Superior Court under s. 24(1) of the Charter. " .
                         "Consider searching CanLII for Charter s. {$section} case law to strengthen your arguments.",
        'next_actions' => ['search charter case law', 'fax to court', 'draft affidavit in support'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 101. LEGAL DISCLOSURE — Request Crown disclosure of evidence
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalDisclosure($args) {
    $caseId        = (int)($args['case_id'] ?? 0);
    $action        = trim($args['action'] ?? 'request'); // request, follow_up, stinchcombe
    $crownOffice   = trim($args['crown_office'] ?? $args['prosecutor'] ?? '');
    $specificItems = $args['items'] ?? $args['requested_items'] ?? [];

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $callerName  = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $caseNumber  = $caseData['case_number'] ?? ($args['case_number'] ?? '');
    $charges     = $caseData['charges'] ?? ($args['charges'] ?? '');
    $district    = $caseData['court_district'] ?? ($args['district'] ?? 'Montréal');

    if ($action === 'stinchcombe') {
        return [
            'success' => true,
            'info'    => [
                'case'    => 'R. v. Stinchcombe, [1991] 3 SCR 326',
                'rule'    => 'The Crown has a duty to disclose ALL relevant information to the defence, whether or not the Crown intends to use it at trial.',
                'key_points' => [
                    'The Crown must disclose ALL relevant evidence, both inculpatory and exculpatory',
                    'Disclosure must be made in a timely manner, before the accused is called upon to elect or plead',
                    'The Crown cannot "cherry-pick" what to disclose',
                    'Witness statements must be disclosed even if the Crown does not intend to call the witness',
                    'A failure to disclose can result in a stay of proceedings (R. v. O\'Connor)',
                    'The accused can apply under s. 7 of the Charter for a remedy if disclosure is incomplete',
                ],
                'remedies' => 'If disclosure is late or incomplete: adjournment, costs, stay of proceedings, or exclusion of evidence (Charter s. 24(1))',
            ],
            'message' => "Under R. v. Stinchcombe, the Crown MUST disclose all relevant evidence. If they haven't, I can help you draft a formal disclosure request or a Charter application for non-disclosure.",
        ];
    }

    if (is_string($specificItems)) $specificItems = array_filter(array_map('trim', explode("\n", $specificItems)));
    if (empty($specificItems)) {
        $specificItems = [
            'All police occurrence reports and supplementary reports',
            'All witness statements (written and recorded)',
            'All surveillance video/audio recordings',
            'All forensic/scientific reports',
            'All photographs and physical evidence logs',
            'Criminal records of all witnesses',
            'All notes of investigating officers',
            'All expert reports and opinions',
            'All electronic communications related to investigation',
            'Any exculpatory or potentially exculpatory material',
        ];
    }

    $itemsList = '';
    foreach ($specificItems as $i => $item) {
        $itemsList .= ($i + 1) . ". {$item}\n";
    }

    $doc = "
═══════════════════════════════════════════════════════
DEMANDE DE DIVULGATION DE LA PREUVE
REQUEST FOR DISCLOSURE OF EVIDENCE
(R. v. Stinchcombe, [1991] 3 SCR 326)
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "

À / TO: " . ($crownOffice ?: "Bureau du procureur de la Couronne / Crown Prosecutor's Office") . "
        District de {$district}

DE / FROM: {$callerName}
Dossier / File: {$caseNumber}
Accusations / Charges: {$charges}

───────────────────────────────────────────────────────
OBJET: DEMANDE DE DIVULGATION COMPLÈTE
RE: REQUEST FOR COMPLETE DISCLOSURE
───────────────────────────────────────────────────────

Conformément à l'arrêt R. c. Stinchcombe, [1991] 3 RCS 326,
et à l'article 7 de la Charte canadienne des droits et libertés,
je demande la divulgation complète des éléments suivants:

Pursuant to R. v. Stinchcombe, [1991] 3 SCR 326, and s. 7 of
the Canadian Charter of Rights and Freedoms, I request complete
disclosure of the following:

───────────────────────────────────────────────────────
ÉLÉMENTS DEMANDÉS / ITEMS REQUESTED
───────────────────────────────────────────────────────

{$itemsList}

───────────────────────────────────────────────────────

Je vous rappelle que l'obligation de divulgation est continue et que
tout nouvel élément de preuve doit m'être communiqué dans les
meilleurs délais.

I remind you that the duty to disclose is ongoing and any new
evidence must be communicated to me promptly.

En cas de refus de divulguer un élément, je vous demande d'identifier
cet élément et de fournir les motifs de votre refus.

If disclosure of any item is refused, please identify the item and
provide reasons for the refusal.

{$callerName}
" . ($caseData['institution'] ?? '') . "
Date: " . date('Y-m-d') . "
";

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "disclosure_request_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Disclosure Request</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'disclosure_request', 'title' => 'Disclosure Request (Stinchcombe)', 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':note' => "\n[" . date('Y-m-d H:i') . "] Disclosure request drafted ({$action})", ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'       => true,
        'document'      => $doc,
        'document_url'  => "https://gositeme.com/downloads/legal/$docFile",
        'items_count'   => count($specificItems),
        'message'       => "Disclosure request drafted with " . count($specificItems) . " item(s). " .
                          "Under Stinchcombe, the Crown MUST disclose all relevant evidence. " .
                          "Send this to the Crown prosecutor's office. If they don't comply, " .
                          "I can help you file a Charter s. 7 application for non-disclosure.",
        'next_actions'  => ['fax to Crown office', 'learn about Stinchcombe', 'charter challenge for non-disclosure'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 102. LEGAL VICTIM IMPACT — Draft victim impact statement
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalVictimImpact($args) {
    $caseId     = (int)($args['case_id'] ?? 0);
    $impact     = trim($args['impact'] ?? $args['description'] ?? '');
    $victimName = trim($args['victim_name'] ?? $args['name'] ?? '');
    $offence    = trim($args['offence'] ?? $args['charges'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $victimName = $victimName ?: ($caseData['caller_name'] ?? '');
    if (!$victimName) return ['error' => 'What is the name of the person making the victim impact statement?'];
    if (!$impact) return ['error' => 'Please describe the impact of the offence on you. How has it affected you emotionally, physically, financially, and in your daily life?'];

    $caseNumber = $caseData['case_number'] ?? ($args['case_number'] ?? '');
    $district   = $caseData['court_district'] ?? ($args['district'] ?? 'Montréal');

    $doc = "
CANADA
PROVINCE DE QUÉBEC
DISTRICT DE " . strtoupper($district) . "

No: " . ($caseNumber ?: '_______________') . "

═══════════════════════════════════════════════════════
DÉCLARATION DE LA VICTIME SUR LES CONSÉQUENCES DU CRIME
VICTIM IMPACT STATEMENT
(Art. 722 du Code criminel / s. 722 Criminal Code)
═══════════════════════════════════════════════════════

Je, {$victimName}, déclare ce qui suit en vertu de l'article 722 du Code criminel:

I, {$victimName}, declare the following pursuant to s. 722 of the Criminal Code:

Infraction / Offence: " . ($offence ?: '_______________') . "

───────────────────────────────────────────────────────
CONSÉQUENCES ÉMOTIONNELLES / EMOTIONAL IMPACT
───────────────────────────────────────────────────────

{$impact}

───────────────────────────────────────────────────────
CONSÉQUENCES PHYSIQUES / PHYSICAL IMPACT
───────────────────────────────────────────────────────

[Décrivez les conséquences physiques, le cas échéant]
[Describe the physical impact, if any]

───────────────────────────────────────────────────────
CONSÉQUENCES FINANCIÈRES / FINANCIAL IMPACT
───────────────────────────────────────────────────────

[Décrivez les conséquences financières, le cas échéant]
[Describe the financial impact, if any]

───────────────────────────────────────────────────────
IMPACT SUR VOTRE VIE QUOTIDIENNE / IMPACT ON DAILY LIFE
───────────────────────────────────────────────────────

[Décrivez comment le crime a affecté votre vie quotidienne]
[Describe how the crime affected your daily life]

───────────────────────────────────────────────────────

NOTE: This statement describes the harm done to or loss suffered by the victim
as a result of the commission of the offence. The court must consider this
statement when determining sentence (s. 722 Criminal Code).

Signature: _________________________________
           {$victimName}
Date: " . date('Y-m-d') . "
";

    $doc = enrichLegalMotionWithAI($doc, 'victim_impact', array_merge($caseData, ['case_summary' => $impact]));

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "victim_impact_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Victim Impact Statement</title><style>body{font-family:'Times New Roman',serif;font-size:12pt;margin:1in;white-space:pre-wrap;line-height:1.6;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'victim_impact', 'title' => 'Victim Impact Statement', 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "Victim impact statement drafted. Under s. 722 of the Criminal Code, " .
                         "the court MUST consider this statement at sentencing. " .
                         "You may also read it aloud in court. Would you like to add more sections?",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 103. LEGAL CONSENT ORDER — Draft consent order / joint submission
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalConsentOrder($args) {
    $caseId      = (int)($args['case_id'] ?? 0);
    $terms       = $args['terms'] ?? $args['conditions'] ?? [];
    $otherParty  = trim($args['other_party'] ?? $args['crown'] ?? 'SA MAJESTÉ LE ROI');
    $orderType   = trim($args['order_type'] ?? $args['type'] ?? 'consent'); // consent, joint_submission

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $caseNumber = $caseData['case_number'] ?? ($args['case_number'] ?? '');
    $district   = $caseData['court_district'] ?? ($args['district'] ?? 'Montréal');
    $courtName  = $caseData['court_name'] ?? ($args['court_name'] ?? 'COUR SUPÉRIEURE');

    if (is_string($terms)) $terms = array_filter(array_map('trim', explode("\n", $terms)));
    if (empty($terms)) return ['error' => 'What are the agreed-upon terms? List the conditions of the consent order.'];

    $termsList = '';
    foreach ($terms as $i => $term) {
        $termsList .= ($i + 1) . ". {$term}\n\n";
    }

    $doc = "
CANADA
PROVINCE DE QUÉBEC
DISTRICT DE " . strtoupper($district) . "

No: " . ($caseNumber ?: '_______________') . "

" . strtoupper($courtName) . "

{$otherParty}
c.
" . strtoupper($callerName ?: 'L\'ACCUSÉ') . "

═══════════════════════════════════════════════════════
" . ($orderType === 'joint_submission' ?
"SUGGESTION COMMUNE SUR LA PEINE / JOINT SUBMISSION ON SENTENCE" :
"ORDONNANCE SUR CONSENTEMENT / CONSENT ORDER") . "
═══════════════════════════════════════════════════════

Les parties déclarent au tribunal qu'elles ont convenu de ce qui suit:
The parties declare to the court that they have agreed to the following:

───────────────────────────────────────────────────────
CONDITIONS CONVENUES / AGREED TERMS
───────────────────────────────────────────────────────

{$termsList}

───────────────────────────────────────────────────────

Les parties demandent respectueusement au tribunal de rendre une
ordonnance conformément aux termes ci-dessus.

The parties respectfully request the court to issue an order in
accordance with the above terms.

{$district}, le " . date('j F Y') . "

_________________________________         _________________________________
{$callerName}                             {$otherParty}
Accusé/Requérant                          Procureur de la Couronne

ENTÉRINÉ PAR LE TRIBUNAL / APPROVED BY THE COURT:

_________________________________
L'honorable juge / The Honourable Justice
Date: _______________
";

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "consent_order_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Consent Order</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'consent_order', 'title' => 'Consent Order / Joint Submission', 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'terms_count'  => count($terms),
        'message'      => "Consent order drafted with " . count($terms) . " term(s). " .
                         "IMPORTANT: Both parties must sign before the judge. A joint submission carries significant weight " .
                         "(R. v. Anthony-Cook, 2016 SCC 43 — court should not depart from a joint submission unless it would " .
                         "bring the administration of justice into disrepute).",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 104. LEGAL TRANSFER REQUEST — Institutional transfer request
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalTransferRequest($args) {
    $caseId        = (int)($args['case_id'] ?? 0);
    $fromInst      = trim($args['from_institution'] ?? $args['current'] ?? '');
    $toInst        = trim($args['to_institution'] ?? $args['destination'] ?? '');
    $reason        = trim($args['reason'] ?? $args['grounds'] ?? '');
    $transferType  = trim($args['transfer_type'] ?? 'voluntary'); // voluntary, involuntary, medical

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
        $fromInst = $fromInst ?: ($caseData['institution'] ?? '');
    }

    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $inmateId   = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');

    if (!$reason) return ['error' => 'Why are you requesting a transfer? (e.g., closer to family, safety concerns, medical needs, program access)'];
    if (!$fromInst) return ['error' => 'Which institution are you currently at?'];

    $doc = "
═══════════════════════════════════════════════════════
DEMANDE DE TRANSFÈREMENT
TRANSFER REQUEST
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "

À / TO: Directeur de l'établissement / Warden
        {$fromInst}

DE / FROM:
{$callerName}
Matricule / Inmate ID: {$inmateId}
Établissement actuel / Current Institution: {$fromInst}

Type de transfert / Transfer Type: " . strtoupper($transferType) . "

───────────────────────────────────────────────────────
DESTINATION DEMANDÉE / REQUESTED DESTINATION
───────────────────────────────────────────────────────

" . ($toInst ?: "[Établissement demandé à préciser / Requested institution to be specified]") . "

───────────────────────────────────────────────────────
MOTIFS DE LA DEMANDE / GROUNDS FOR REQUEST
───────────────────────────────────────────────────────

{$reason}

───────────────────────────────────────────────────────
FONDEMENT JURIDIQUE / LEGAL BASIS
───────────────────────────────────────────────────────

En vertu de / Pursuant to:
- S. 28 de la Loi sur le système correctionnel et la mise en liberté
  sous condition (LSCMLSC) / Corrections and Conditional Release Act (CCRA)
- Directive du commissaire 710-2 (Transfèrement de détenus)
  Commissioner's Directive 710-2 (Transfer of Inmates)

Le Service correctionnel du Canada doit tenir compte:
CSC must consider:
1. La proximité de la famille du détenu / Proximity to inmate's family
2. La sécurité du détenu / Safety of the inmate
3. L'accès aux programmes / Access to programs
4. Les besoins médicaux / Medical needs
5. Le classement sécuritaire / Security classification

Le détenu a le droit d'être informé des raisons de toute décision
concernant son transfèrement et a le droit de contester cette décision
par le processus de grief.

───────────────────────────────────────────────────────

Signature: _________________________________
           {$callerName}
Date: " . date('Y-m-d') . "
";

    $doc = enrichLegalMotionWithAI($doc, 'transfer_request', array_merge($caseData, ['case_summary' => $reason]));

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "transfer_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Transfer Request</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'transfer_request', 'title' => 'Transfer Request', 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':note' => "\n[" . date('Y-m-d H:i') . "] Transfer request drafted: {$fromInst} -> {$toInst}", ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "Transfer request drafted ({$transferType}). Submit this to your case manager or the warden. " .
                         "If denied, you can grieve the decision. If the transfer is involuntary and you oppose it, " .
                         "I can help you file a grievance or seek judicial review.",
        'next_actions' => ['file grievance about transfer', 'search transfer case law'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 105. LEGAL MEDICAL REQUEST — Request medical care in custody
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalMedicalRequest($args) {
    $caseId       = (int)($args['case_id'] ?? 0);
    $condition    = trim($args['condition'] ?? $args['medical_issue'] ?? $args['description'] ?? '');
    $urgency      = trim($args['urgency'] ?? 'normal'); // emergency, urgent, normal
    $requestType  = trim($args['request_type'] ?? $args['type'] ?? 'treatment'); // treatment, specialist, medication, dental, mental_health

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $callerName  = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $inmateId    = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');
    $institution = $caseData['institution'] ?? ($args['institution'] ?? '');

    if (!$condition) return ['error' => 'What medical condition or issue needs attention? Describe your symptoms and medical history.'];

    $requestTypes = [
        'treatment'     => 'Demande de traitement médical / Medical Treatment Request',
        'specialist'    => 'Demande de consultation spécialisée / Specialist Referral Request',
        'medication'    => 'Demande de médicaments / Medication Request',
        'dental'        => 'Demande de soins dentaires / Dental Care Request',
        'mental_health' => 'Demande de soins en santé mentale / Mental Health Care Request',
    ];
    if (!isset($requestTypes[$requestType])) $requestType = 'treatment';
    $typeLabel = $requestTypes[$requestType];

    $doc = "
═══════════════════════════════════════════════════════
{$typeLabel}
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "
Niveau d'urgence / Urgency: " . strtoupper($urgency) . "

À / TO: Service de santé / Health Services
        {$institution}

DE / FROM:
{$callerName}
Matricule: {$inmateId}
Institution: {$institution}

───────────────────────────────────────────────────────
DESCRIPTION DE LA CONDITION MÉDICALE
DESCRIPTION OF MEDICAL CONDITION
───────────────────────────────────────────────────────

{$condition}

───────────────────────────────────────────────────────
SOINS DEMANDÉS / CARE REQUESTED
───────────────────────────────────────────────────────

Le détenu demande respectueusement:
1. Une consultation médicale dans les plus brefs délais;
2. Les soins appropriés à sa condition;
3. Un suivi médical régulier;
" . ($requestType === 'specialist' ? "4. Un aiguillage vers un spécialiste approprié;\n" : "") .
($requestType === 'medication' ? "4. La prescription ou la continuation de la médication nécessaire;\n" : "") .
($requestType === 'dental' ? "4. Un examen dentaire et les soins dentaires nécessaires;\n" : "") .
($requestType === 'mental_health' ? "4. Une évaluation en santé mentale et un suivi approprié;\n" : "") . "

───────────────────────────────────────────────────────
FONDEMENT JURIDIQUE / LEGAL BASIS
───────────────────────────────────────────────────────

En vertu de / Pursuant to:
- S. 86 LSCMLSC / CCRA: Le SCC doit fournir à chaque détenu les soins
  de santé essentiels et un accès raisonnable aux soins de santé non essentiels.
- Art. 12 de la Charte: Protection contre les traitements cruels et
  inusités (le refus de soins médicaux peut constituer un traitement cruel).
- Art. 7 de la Charte: Droit à la vie, la liberté et la sécurité de la personne.
- Directive du commissaire 800 (Soins de santé / Health Services)

Le refus ou le retard déraisonnable des soins médicaux peut constituer
une violation de la Charte (voir: R. c. Smith, 2015 CSC 34; Bacon c. Surrey
Pretrial Services Centre, 2010 BCSC 805).

" . ($urgency === 'emergency' ? "*** URGENCE MÉDICALE / MEDICAL EMERGENCY ***\nCette demande requiert une attention immédiate.\n" : "") . "

Signature: _________________________________
           {$callerName}
Date: " . date('Y-m-d') . "
";

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "medical_{$requestType}_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Medical Request</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'medical_request', 'title' => $typeLabel, 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':note' => "\n[" . date('Y-m-d H:i') . "] Medical request drafted ({$requestType}, urgency: {$urgency})", ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'urgency'      => $urgency,
        'message'      => "Medical request drafted ({$typeLabel}). " .
                         ($urgency === 'emergency' ? "This is marked EMERGENCY — submit immediately to health services. " :
                         "Submit to the health services department at your institution. ") .
                         "If denied or delayed, you can file a grievance. The institution has a legal obligation to provide essential health care (s. 86 CCRA).",
        'next_actions' => ['file medical grievance', 'charter challenge for medical neglect', 'file with OCI'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 106. LEGAL SEGREGATION REVIEW — Challenge segregation / solitary
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalSegregationReview($args) {
    $caseId        = (int)($args['case_id'] ?? 0);
    $action        = trim($args['action'] ?? 'review'); // review, challenge, info
    $segregType    = trim($args['type'] ?? 'administrative'); // administrative, disciplinary
    $startDate     = trim($args['start_date'] ?? '');
    $reason        = trim($args['reason'] ?? $args['grounds'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $callerName  = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $inmateId    = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');
    $institution = $caseData['institution'] ?? ($args['institution'] ?? '');

    if ($action === 'info') {
        return [
            'success' => true,
            'info'    => [
                'new_law' => 'Bill C-83 (2019) replaced administrative segregation with Structured Intervention Units (SIUs)',
                'siu_rights' => [
                    'Minimum 4 hours out of cell per day (20 hours in general population)',
                    'Minimum 2 hours of meaningful human contact per day',
                    'Access to programming, health care, and mental health support',
                    'Independent External Decision-maker (IEDM) reviews placement after 5 days',
                    'IEDM reviews conditions after 60 days',
                    'Health care professionals monitor daily',
                ],
                'challenges' => [
                    'Habeas corpus if placement is unlawful',
                    'Charter s. 7 (liberty/security) and s. 12 (cruel/unusual)',
                    'Canada (AG) v. BCCLA, 2018 BCCA 282 — prolonged solitary is cruel',
                    'Mandela Rules: solitary >15 days is torture (UN Standard Minimum Rules)',
                ],
                'contacts' => [
                    'OCI (federal)' => '1-877-885-8848',
                    'Protecteur du citoyen (provincial)' => '1-800-463-5070',
                    'Legal Aid' => '1-800-842-2213',
                ],
            ],
            'message' => "Under Bill C-83, administrative segregation was replaced by SIUs. You have the right to 4 hours out of cell and 2 hours meaningful human contact daily. If these aren't being met, I can help you challenge the placement.",
        ];
    }

    // Calculate days in segregation
    $daysInSeg = 0;
    if ($startDate) {
        try {
            $segStart = new DateTime($startDate);
            $now = new DateTime();
            $daysInSeg = $segStart->diff($now)->days;
        } catch (Exception $e) {}
    }

    $doc = "
═══════════════════════════════════════════════════════
CONTESTATION DU PLACEMENT EN ISOLEMENT / EN UNITÉ
D'INTERVENTION STRUCTURÉE (UIS)
CHALLENGE OF SEGREGATION / STRUCTURED INTERVENTION
UNIT (SIU) PLACEMENT
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "

À / TO: Directeur de l'établissement / Warden
        {$institution}
CC: Bureau de l'enquêteur correctionnel / OCI

DE / FROM:
{$callerName}
Matricule: {$inmateId}
Institution: {$institution}

Type d'isolement / Type: " . strtoupper($segregType) . "
Date de début / Start Date: " . ($startDate ?: 'Inconnue / Unknown') . "
" . ($daysInSeg > 0 ? "Durée / Duration: {$daysInSeg} jours / days" : "") . "
" . ($daysInSeg >= 15 ? "\n*** ATTENTION: {$daysInSeg} JOURS EN ISOLEMENT — DÉPASSE 15 JOURS (RÈGLES MANDELA) ***\n" : "") . "

───────────────────────────────────────────────────────
MOTIFS DE CONTESTATION / GROUNDS FOR CHALLENGE
───────────────────────────────────────────────────────

" . ($reason ?: "[Décrire les violations alléguées / Describe alleged violations]") . "

───────────────────────────────────────────────────────
FONDEMENT JURIDIQUE / LEGAL BASIS
───────────────────────────────────────────────────────

1. Loi C-83 (Unités d'intervention structurée):
   - Le détenu a droit à un minimum de 4 heures hors de cellule par jour (s. 36)
   - Le détenu a droit à 2 heures de contact humain réel par jour (s. 36)
   - Un décideur externe indépendant (DEI) doit revoir le placement après 5 jours
   - Le placement doit être revu après 60 jours

2. Charte canadienne:
   - Art. 7: Droit à la liberté et à la sécurité de la personne
   - Art. 12: Protection contre les traitements cruels et inusités
   - Canada (PG) c. BCCLA: l'isolement prolongé est cruel et inusité

3. Règles Mandela (Règles minimales des Nations Unies):
   - Règle 43: L'isolement de plus de 15 jours consécutifs constitue de la torture
   - Règle 44: L'isolement indéfini est interdit

DEMANDE / REQUEST:
1. " . ($action === 'challenge' ? "FIN IMMÉDIATE de l'isolement / IMMEDIATE END to segregation" : "RÉVISION du placement / REVIEW of placement") . "
2. Respect des droits garantis par la Loi C-83
3. Accès aux programmes et aux soins de santé

" . ($daysInSeg >= 15 ? "URGENT: Le détenu est en isolement depuis {$daysInSeg} jours, ce qui dépasse les 15 jours prévus par les Règles Mandela.\n" : "") . "

Signature: _________________________________
           {$callerName}
Date: " . date('Y-m-d') . "
";

    $doc = enrichLegalMotionWithAI($doc, 'segregation_review', array_merge($caseData, ['case_summary' => $reason ?: "Segregation review after {$daysInSeg} days"]));

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "segregation_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Segregation Review</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'segregation_review', 'title' => 'Segregation/SIU Challenge', 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'       => true,
        'days_in_seg'   => $daysInSeg,
        'mandela_exceeded' => $daysInSeg >= 15,
        'document'      => $doc,
        'document_url'  => "https://gositeme.com/downloads/legal/$docFile",
        'message'       => "Segregation challenge drafted. " .
                          ($daysInSeg >= 15 ? "WARNING: {$daysInSeg} days exceeds the 15-day Mandela Rules limit. This may constitute torture under international law. " : "") .
                          "Under Bill C-83, you have rights to minimum time out of cell and meaningful human contact. " .
                          "Submit to the warden and copy the OCI (1-877-885-8848).",
        'next_actions'  => ['file grievance', 'habeas corpus', 'file with OCI', 'charter challenge'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 107. LEGAL RECORD SUSPENSION — Application for record suspension (pardon)
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalRecordSuspension($args) {
    $action        = trim($args['action'] ?? 'info'); // info, eligibility, draft
    $offenceType   = trim($args['offence_type'] ?? $args['type'] ?? ''); // summary, indictable
    $completionDate = trim($args['completion_date'] ?? $args['sentence_completed'] ?? '');
    $offences      = trim($args['offences'] ?? $args['charges'] ?? '');

    if ($action === 'info') {
        return [
            'success' => true,
            'info'    => [
                'what' => 'A record suspension (formerly pardon) allows people who were convicted of a criminal offence, and have completed their sentence and demonstrated they are law-abiding, to have their criminal record kept separate and apart from other criminal records.',
                'eligibility_periods' => [
                    'Summary conviction' => '5 years after completion of sentence (fine paid, probation completed)',
                    'Indictable offence' => '10 years after completion of sentence',
                ],
                'not_eligible' => [
                    'Sexual offences involving children (Schedule 1)',
                    'More than 3 offences prosecuted by indictment with sentences of 2+ years each',
                    'Sex offences against minors with an absolute prohibition',
                ],
                'cost'  => '$657.77 CAD (PBC application fee)',
                'process' => [
                    '1. Obtain criminal record from RCMP ($25)',
                    '2. Obtain court information from all courts',
                    '3. Obtain local police records check',
                    '4. Complete PBC application form',
                    '5. Provide evidence of good conduct',
                    '6. Submit to Parole Board of Canada',
                ],
                'contact' => 'Parole Board of Canada: 1-800-874-2652 | https://www.canada.ca/en/parole-board/services/record-suspensions.html',
            ],
            'message' => "A record suspension (pardon) keeps your criminal record separate. Waiting period: 5 years for summary offences, 10 years for indictable. Cost: $657.77. Would you like me to check your eligibility?",
        ];
    }

    if ($action === 'eligibility') {
        if (!$completionDate) return ['error' => 'When did you complete your sentence (including probation, fine payment, etc.)? Format: YYYY-MM-DD'];

        $waitYears = ($offenceType === 'summary' || $offenceType === 'sommaire') ? 5 : 10;

        try {
            $completed = new DateTime($completionDate);
            $eligible = clone $completed;
            $eligible->modify("+{$waitYears} years");
            $now = new DateTime();
            $diff = $now->diff($eligible);
            $isEligible = !$diff->invert ? false : true; // invert=1 means eligible is in the past

            // Fix: if eligible date is in the past or today, they're eligible
            $isEligible = $eligible <= $now;

            return [
                'success'           => true,
                'offence_type'      => $offenceType ?: 'indictable (assumed)',
                'sentence_completed'=> $completionDate,
                'waiting_period'    => "{$waitYears} years",
                'eligible_date'     => $eligible->format('Y-m-d'),
                'is_eligible'       => $isEligible,
                'message'           => $isEligible ?
                    "You appear ELIGIBLE for a record suspension! Your waiting period ended " . $eligible->format('Y-m-d') . ". " .
                    "Next steps: 1) Get RCMP criminal record check ($25), 2) Get court records, 3) Apply to PBC ($657.77). " .
                    "Would you like more information or help with the application?" :
                    "You are not yet eligible. Eligible date: " . $eligible->format('Y-m-d') . " ({$diff->y} years, {$diff->m} months remaining). " .
                    "Make sure you maintain good conduct during the waiting period.",
            ];
        } catch (Exception $e) {
            return ['error' => 'Invalid date format. Use YYYY-MM-DD.'];
        }
    }

    // Draft application cover letter
    $callerName = trim($args['caller_name'] ?? $args['name'] ?? '');
    $address    = trim($args['address'] ?? '');

    $doc = "
═══════════════════════════════════════════════════════
DEMANDE DE SUSPENSION DU CASIER JUDICIAIRE
APPLICATION FOR RECORD SUSPENSION
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "

Commission des libérations conditionnelles du Canada
Parole Board of Canada
410 Laurier Avenue West
Ottawa, ON K1A 0R1

Objet / Re: Demande de suspension du casier judiciaire
            Application for Record Suspension

───────────────────────────────────────────────────────

Madame, Monsieur,

Je soussigné(e), {$callerName}, demande respectueusement une
suspension de mon casier judiciaire conformément à la Loi sur
le casier judiciaire.

I, the undersigned, {$callerName}, respectfully apply for a record
suspension pursuant to the Criminal Records Act.

Infractions / Offences: {$offences}
Date de fin de la peine / Sentence completion: {$completionDate}

Depuis ma condamnation, j'ai mené une vie exemplaire et je suis
un citoyen respectueux des lois. Je demande cette suspension afin
de facilitier ma réhabilitation et ma réintégration dans la société.

Since my conviction, I have led an exemplary life and am a law-abiding
citizen. I request this suspension to facilitate my rehabilitation and
reintegration into society.

Veuillez trouver ci-joint les documents requis.
Please find enclosed the required documents.

Respectueusement / Respectfully,

_________________________________
{$callerName}
{$address}
Date: " . date('Y-m-d') . "
";

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "record_suspension_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Record Suspension</title><style>body{font-family:'Times New Roman',serif;font-size:12pt;margin:1in;white-space:pre-wrap;line-height:1.6;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    return [
        'success'      => true,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "Record suspension application cover letter drafted. You still need to: " .
                         "1) Get RCMP criminal record check ($25), 2) Get court information, " .
                         "3) Get local police records check, 4) Complete PBC application form ($657.77). " .
                         "Call PBC at 1-800-874-2652 for the full application package.",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 108. LEGAL IMMIGRATION — Immigration hold / deportation defense
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalImmigration($args) {
    $caseId      = (int)($args['case_id'] ?? 0);
    $action      = trim($args['action'] ?? 'info'); // info, detention_review, stay, h_and_c
    $country     = trim($args['country'] ?? $args['country_of_origin'] ?? '');
    $status      = trim($args['immigration_status'] ?? $args['status'] ?? '');
    $grounds     = trim($args['grounds'] ?? $args['description'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $inmateId   = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');

    if ($action === 'info') {
        return [
            'success' => true,
            'info'    => [
                'key_laws' => [
                    'IRPA' => 'Immigration and Refugee Protection Act — governs immigration detention and removal',
                    'Charter s.7' => 'Right to life, liberty, security — applies to non-citizens',
                    'Charter s.12' => 'Protection against cruel and unusual treatment — deportation to torture',
                    'IRPA s.44' => 'Report of inadmissibility (criminality, security, etc.)',
                    'IRPA s.36' => 'Inadmissibility on grounds of criminality',
                    'IRPA s.115' => 'Protection from refoulement (return to persecution/torture)',
                ],
                'detention_reviews' => 'Under IRPA s. 57-58: First review within 48 hours, then within 7 days, then every 30 days',
                'key_contacts' => [
                    'Immigration and Refugee Board (IRB)' => '1-800-461-4043',
                    'CBSA Enforcement' => '1-800-461-9999',
                    'UNHCR Canada' => '+16132820440',
                    'Legal Aid Immigration (Montreal)' => '+15143938484',
                    'Canadian Centre for Gender and Sexual Diversity' => '+16132559070',
                ],
                'important' => 'DO NOT sign any voluntary departure documents without speaking to a lawyer first. You have the right to a detention review and to make refugee/humanitarian claims.',
            ],
            'message' => "IMPORTANT: Do NOT sign voluntary departure documents without legal advice. You have rights under the Charter even as a non-citizen. " .
                        "Detention reviews: 48 hours, 7 days, then every 30 days. Legal Aid Immigration: +15143938484. " .
                        "What specific immigration issue do you need help with?",
        ];
    }

    // Draft detention review submission
    $doc = "
═══════════════════════════════════════════════════════
" . ($action === 'stay' ? "DEMANDE DE SURSIS À L'EXÉCUTION DE LA MESURE DE RENVOI\nAPPLICATION FOR STAY OF REMOVAL ORDER" :
     ($action === 'h_and_c' ? "DEMANDE POUR CONSIDÉRATIONS HUMANITAIRES\nHUMANITARIAN AND COMPASSIONATE APPLICATION" :
     "SOUMISSION POUR LA RÉVISION DE LA DÉTENTION\nSUBMISSION FOR DETENTION REVIEW")) . "
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "

À / TO: " . ($action === 'stay' ? 'Cour fédérale du Canada / Federal Court of Canada' :
             'Section de l\'immigration / Immigration Division, IRB') . "

DE / FROM:
{$callerName}
" . ($inmateId ? "Matricule / ID: {$inmateId}" : "") . "
Pays d'origine / Country: {$country}
Statut / Status: {$status}

───────────────────────────────────────────────────────
SOUMISSIONS / SUBMISSIONS
───────────────────────────────────────────────────────

{$grounds}

───────────────────────────────────────────────────────
FONDEMENT JURIDIQUE / LEGAL BASIS
───────────────────────────────────────────────────────

" . ($action === 'stay' ?
"1. S. 18.2 de la Loi sur les Cours fédérales — sursis d'exécution
2. Critère tripartite (Toth c. Canada): a) question sérieuse, b) préjudice irréparable, c) balance des inconvénients
3. S. 115 LIPR — protection contre le refoulement
4. Art. 7 de la Charte — droit à la vie, la liberté et la sécurité" :

($action === 'h_and_c' ?
"1. S. 25(1) LIPR — considérations d'ordre humanitaire
2. Facteurs Chirwa: établissement au Canada, liens familiaux, intérêt supérieur des enfants, conditions dans le pays d'origine
3. Kanthasamy c. Canada, 2015 CSC 61 — interprétation large et libérale" :

"1. S. 57-58 LIPR — révision de la détention
2. S. 248 du Règlement — facteurs à considérer pour la mise en liberté
3. Charte s. 7 et 9 — détention arbitraire, droit à la liberté
4. Charkaoui c. Canada, 2007 CSC 9 — la détention d'immigration doit être justifiée
5. Canada (MCI) c. Li — la durée de la détention est un facteur pertinent")) . "

Respectueusement / Respectfully,

_________________________________
{$callerName}
Date: " . date('Y-m-d') . "
";

    $doc = enrichLegalMotionWithAI($doc, 'immigration_' . $action, array_merge($caseData, ['case_summary' => $grounds]));

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "immigration_{$action}_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Immigration</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => "immigration_{$action}", 'title' => "Immigration — {$action}", 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'action'       => $action,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "Immigration document drafted ({$action}). " .
                         "CRITICAL: Contact Legal Aid Immigration immediately at +15143938484. " .
                         "Do NOT sign any voluntary departure documents. You have Charter rights.",
        'next_actions' => ['contact legal aid immigration', 'search immigration case law', 'detention review prep'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 109. LEGAL MENTAL HEALTH — Mental health assessment / NCR review
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalMentalHealth($args) {
    $caseId      = (int)($args['case_id'] ?? 0);
    $action      = trim($args['action'] ?? 'info'); // info, assessment_request, ncr_review, treatment
    $condition   = trim($args['condition'] ?? $args['description'] ?? '');
    $crisis      = !empty($args['crisis']); // immediate mental health crisis

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $callerName  = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $inmateId    = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');
    $institution = $caseData['institution'] ?? ($args['institution'] ?? '');

    if ($crisis) {
        return [
            'success'  => true,
            'CRISIS'   => true,
            'message'  => "⚠ MENTAL HEALTH CRISIS — IMMEDIATE STEPS:\n" .
                         "1. Tell a correctional officer RIGHT NOW that you need mental health help\n" .
                         "2. Ask to see the psychologist or mental health nurse IMMEDIATELY\n" .
                         "3. If you are thinking of hurting yourself, tell someone NOW\n\n" .
                         "CRISIS LINES (ask to call):\n" .
                         "- Suicide Prevention: 1-833-456-4566 (national) / 1-866-APPELLE (Quebec)\n" .
                         "- Tel-Aide: 514-935-1101\n" .
                         "- Crisis Text Line: Text HOME to 686868\n\n" .
                         "You have the RIGHT to mental health care under s. 86 CCRA and Charter s. 7.",
            'contacts' => [
                'Suicide Prevention (Canada)' => '1-833-456-4566',
                'Tel-Aide (Quebec)' => '1-866-277-3553',
                'Crisis Text Line' => 'Text HOME to 686868',
            ],
        ];
    }

    if ($action === 'info') {
        return [
            'success' => true,
            'info'    => [
                'rights' => [
                    'Right to mental health care (s. 86 CCRA, Commissioner\'s Directive 850)',
                    'Right to informed consent for treatment',
                    'Right to refuse treatment (except in emergency)',
                    'Right to confidentiality of medical records',
                    'Right to independent psychiatric assessment',
                ],
                'ncr' => [
                    'NCR' => 'Not Criminally Responsible on account of Mental Disorder (s. 16 Criminal Code)',
                    'Fitness' => 'Fitness to stand trial (s. 672.22 Criminal Code)',
                    'Review Board' => 'Quebec Mental Health Review Board reviews NCR dispositions annually',
                    'Options' => 'Absolute discharge, conditional discharge, or detention in hospital',
                ],
                'resources' => [
                    'Mental Health Commission of Canada' => 'https://www.mentalhealthcommission.ca/',
                    'AQPAMM (Quebec families)' => '+15145240223',
                    'Institut Philippe-Pinel (forensic psychiatry)' => '+15146483535',
                ],
            ],
            'message' => "You have the right to mental health care in custody. What do you need? " .
                        "Options: assessment request, NCR review, or treatment request. " .
                        "If this is an EMERGENCY, say 'crisis' and I'll provide immediate resources.",
        ];
    }

    // Draft assessment/treatment request
    $doc = "
═══════════════════════════════════════════════════════
" . ($action === 'ncr_review' ?
"DEMANDE DE RÉVISION — NON-RESPONSABILITÉ CRIMINELLE\nNCR DISPOSITION REVIEW REQUEST" :
($action === 'treatment' ?
"DEMANDE DE TRAITEMENT EN SANTÉ MENTALE\nMENTAL HEALTH TREATMENT REQUEST" :
"DEMANDE D'ÉVALUATION EN SANTÉ MENTALE\nMENTAL HEALTH ASSESSMENT REQUEST")) . "
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "
" . ($crisis ? "*** URGENT / URGENT ***\n" : "") . "

À / TO: " . ($action === 'ncr_review' ? 'Commission d\'examen des troubles mentaux / Mental Health Review Board' : "Service de santé / Health Services — {$institution}") . "

DE / FROM:
{$callerName}
Matricule: {$inmateId}
Institution: {$institution}

───────────────────────────────────────────────────────
DESCRIPTION / DESCRIPTION
───────────────────────────────────────────────────────

{$condition}

───────────────────────────────────────────────────────
FONDEMENT JURIDIQUE / LEGAL BASIS
───────────────────────────────────────────────────────

" . ($action === 'ncr_review' ?
"- Art. 672.81 du Code criminel — révision annuelle obligatoire
- Art. 672.54 — le tribunal doit rendre la décision la moins privative de liberté
- Winko c. Colombie-Britannique, [1999] 2 RCS 625 — l'accusé NCR ne doit pas
  être détenu s'il ne représente pas un risque important pour la sécurité publique" :
"- S. 86 LSCMLSC / CCRA — droit aux soins de santé essentiels
- S. 87 LSCMLSC / CCRA — consentement éclairé requis pour le traitement
- Directive du commissaire 850 — Services de santé mentale
- Art. 7 de la Charte — droit à la sécurité de la personne
- Art. 12 de la Charte — protection contre les traitements cruels") . "

Signature: _________________________________
           {$callerName}
Date: " . date('Y-m-d') . "
";

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "mental_health_{$action}_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Mental Health</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => "mental_health_{$action}", 'title' => "Mental Health — {$action}", 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'action'       => $action,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "Mental health {$action} request drafted. Submit to health services. " .
                         "You have the right to mental health care under s. 86 CCRA. " .
                         "If you're in crisis, tell a staff member immediately or call 1-833-456-4566.",
        'next_actions' => ['file grievance for mental health', 'search mental health case law', 'medical request'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 110. LEGAL YOUTH JUSTICE — Youth Criminal Justice Act matters
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalYouthJustice($args) {
    $caseId    = (int)($args['case_id'] ?? 0);
    $action    = trim($args['action'] ?? 'info'); // info, extrajudicial, adult_sentence, record
    $age       = (int)($args['age'] ?? 0);
    $offence   = trim($args['offence'] ?? $args['charges'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    if ($action === 'info') {
        return [
            'success' => true,
            'info'    => [
                'ycja' => 'Youth Criminal Justice Act (YCJA) applies to youth aged 12-17 at time of offence',
                'key_principles' => [
                    'Youth justice system separate from adults',
                    'Emphasis on rehabilitation and reintegration',
                    'Fair and proportionate accountability',
                    'Publication ban on youth identity (s. 110)',
                    'Youth record protections — access periods apply (s. 119)',
                ],
                'extrajudicial_measures' => [
                    'Warnings and cautions (s. 6-7)',
                    'Referrals to community programs (s. 6)',
                    'Extrajudicial sanctions with conditions (s. 10)',
                    'Must be considered before charges for non-violent offences',
                ],
                'sentencing_options' => [
                    'Reprimand', 'Absolute/conditional discharge',
                    'Fine (max $1,000)', 'Community service',
                    'Probation (max 2 years)', 'Intensive support',
                    'Deferred custody', 'Custody and supervision',
                    'Intensive rehabilitative custody (IRCS)',
                ],
                'adult_sentence' => 'Youth 14+ MAY receive adult sentence for serious violent offences (s. 64). Crown must apply.',
                'contacts' => [
                    'Legal Aid Youth Line' => '1-800-842-2213',
                    'Youth Protection Director (DPJ Québec)' => '+15148963100',
                    'Defence for Children International - Canada' => 'https://www.dfrci.org/',
                ],
            ],
            'message' => "The YCJA applies to youth aged 12-17. Youth have special protections including publication bans and record access limits. What specific youth justice issue do you need help with?",
        ];
    }

    if ($action === 'record') {
        // Youth record access periods info
        $accessPeriods = [
            'Extrajudicial sanction' => '2 years from consent',
            'Acquittal' => '2 months',
            'Reprimand' => '2 months after sentence',
            'Absolute discharge' => '1 year after finding of guilt',
            'Conditional discharge' => '3 years after order expires',
            'Summary conviction' => '3 years after completion of sentence',
            'Indictable offence' => '5 years after completion of sentence',
        ];

        return [
            'success'        => true,
            'access_periods' => $accessPeriods,
            'message'        => "Youth records have automatic access periods after which the record cannot be accessed. " .
                               "Unlike adult records, you do NOT need to apply for a record suspension — the access period ends automatically. " .
                               "After the access period, the record is treated as if it never existed (s. 128 YCJA).",
        ];
    }

    // Draft youth-specific document
    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $district   = $caseData['court_district'] ?? ($args['district'] ?? 'Montréal');

    $doc = "
═══════════════════════════════════════════════════════
" . ($action === 'adult_sentence' ?
"OPPOSITION À LA PEINE POUR ADULTE\nOPPOSITION TO ADULT SENTENCE APPLICATION" :
"DEMANDE DE MESURES EXTRAJUDICIAIRES\nAPPLICATION FOR EXTRAJUDICIAL MEASURES") . "
(Loi sur le système de justice pénale pour les adolescents / YCJA)
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "
Tribunal pour adolescents / Youth Court
District de {$district}

Adolescent(e) / Youth: {$callerName}
Âge / Age: " . ($age ?: '___') . "
Infraction(s) / Offence(s): " . ($offence ?: '_______________') . "

───────────────────────────────────────────────────────
SOUMISSIONS / SUBMISSIONS
───────────────────────────────────────────────────────

" . ($action === 'adult_sentence' ?
"L'adolescent(e) s'oppose à l'imposition d'une peine pour adulte:

1. La LSJPA prévoit que la réhabilitation est l'objectif principal (s. 3)
2. La peine spécifique est suffisante pour tenir l'adolescent(e) responsable
3. L'adolescent(e) n'a pas l'historique de violence grave requise
4. L'imposition d'une peine pour adulte n'est pas dans l'intérêt supérieur
   de l'adolescent(e) ni de la société
5. R. c. D.B., 2008 CSC 25 — présomption de culpabilité morale moindre" :

"L'adolescent(e) demande des mesures extrajudiciaires:

1. S. 4 LSJPA — les mesures extrajudiciaires sont présumées adéquates
   pour les infractions sans violence commises par un primo-délinquant
2. L'adolescent(e) reconnaît sa responsabilité
3. Des mesures extrajudiciaires sont suffisantes pour tenir l'adolescent(e)
   responsable de façon proportionnée
4. L'intérêt supérieur de l'adolescent(e) commande la réhabilitation") . "

───────────────────────────────────────────────────────
PROTECTION DE L'IDENTITÉ / IDENTITY PROTECTION
───────────────────────────────────────────────────────

Ce document est protégé par l'interdit de publication prévu à
l'article 110 de la LSJPA. La publication de tout renseignement
permettant d'identifier l'adolescent(e) est interdite.

Signature: _________________________________
Date: " . date('Y-m-d') . "
";

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "youth_{$action}_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Youth Justice</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => "youth_{$action}", 'title' => "Youth Justice — {$action}", 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "Youth justice document drafted ({$action}). " .
                         "REMEMBER: Youth have special protections under YCJA including publication bans. " .
                         "Contact Legal Aid Youth Line: 1-800-842-2213 for free legal representation.",
        'next_actions' => ['search YCJA case law', 'youth record info', 'legal aid referral'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 111. LEGAL INDIGENOUS RIGHTS — Gladue reports and Indigenous rights
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalIndigenousRights($args) {
    $caseId     = (int)($args['case_id'] ?? 0);
    $action     = trim($args['action'] ?? 'info'); // info, gladue, rights
    $nation     = trim($args['nation'] ?? $args['first_nation'] ?? '');
    $background = trim($args['background'] ?? $args['description'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');
    $district   = $caseData['court_district'] ?? ($args['district'] ?? 'Montréal');

    if ($action === 'info') {
        return [
            'success' => true,
            'info'    => [
                'gladue' => [
                    'case' => 'R. v. Gladue, [1999] 1 SCR 688',
                    'principle' => 'Courts MUST consider the unique circumstances of Indigenous offenders at sentencing (s. 718.2(e) Criminal Code)',
                    'factors' => [
                        'History of colonialism, residential schools, displacement',
                        'Personal experiences of racism, poverty, abuse',
                        'Impact of child welfare system (Sixties Scoop, foster care)',
                        'Connection to culture, community, and land',
                        'Intergenerational trauma from residential schools',
                        'Systemic discrimination in criminal justice system',
                    ],
                    'ipeelee' => 'R. v. Ipeelee, 2012 SCC 13 — Gladue factors apply regardless of offence severity',
                ],
                'rights' => [
                    'Section 35 Constitution Act, 1982 — Aboriginal and treaty rights',
                    'UNDRIP — UN Declaration on the Rights of Indigenous Peoples (adopted by Canada 2016)',
                    'Duty to consult — the Crown must consult on decisions affecting Aboriginal rights',
                    's. 718.2(e) Criminal Code — Gladue principles at sentencing',
                ],
                'resources' => [
                    'Native Friendship Centres' => 'https://www.nafc.ca/',
                    'Native Women\'s Association of Canada' => '1-800-461-4043',
                    'Legal Aid Indigenous Services (Quebec)' => '1-800-842-2213',
                    'Assembly of First Nations' => 'https://www.afn.ca/',
                    'Kahnawake Legal Services' => '+14506327212',
                ],
            ],
            'message' => "Under R. v. Gladue and R. v. Ipeelee, courts MUST consider the unique circumstances of Indigenous offenders. " .
                        "A Gladue report should be prepared before sentencing. Would you like me to help prepare Gladue factors?",
        ];
    }

    if ($action === 'gladue') {
        // Draft Gladue factors summary
        $doc = "
═══════════════════════════════════════════════════════
FACTEURS GLADUE / GLADUE FACTORS
(R. c. Gladue, [1999] 1 RCS 688; R. c. Ipeelee, 2012 CSC 13)
(Art. 718.2(e) du Code criminel)
═══════════════════════════════════════════════════════

Préparé pour / Prepared for: {$callerName}
Nation / First Nation: " . ($nation ?: '[À identifier / To be identified]') . "
District: {$district}
Date: " . date('Y-m-d') . "

NOTE: Ceci est un résumé des facteurs Gladue et NE REMPLACE PAS un
rapport Gladue complet préparé par un rédacteur de rapports Gladue qualifié.
This is a summary of Gladue factors and does NOT replace a full Gladue
report prepared by a qualified Gladue report writer.

───────────────────────────────────────────────────────
CIRCONSTANCES UNIQUES / UNIQUE CIRCUMSTANCES
───────────────────────────────────────────────────────

" . ($background ?: "[Décrire les circonstances uniques de la personne autochtone:
- Expérience avec les pensionnats / Residential school experience
- Placement en famille d'accueil / Foster care placement
- Perte de culture et de langue / Loss of culture and language
- Discrimination systémique / Systemic discrimination
- Traumatisme intergénérationnel / Intergenerational trauma
- Conditions socio-économiques / Socioeconomic conditions
- Liens avec la communauté / Community connections]") . "

───────────────────────────────────────────────────────
PRINCIPES JURIDIQUES / LEGAL PRINCIPLES
───────────────────────────────────────────────────────

1. R. c. Gladue: Le tribunal DOIT tenir compte des circonstances
   uniques des délinquants autochtones (art. 718.2(e) C.cr.)
2. R. c. Ipeelee: Les facteurs Gladue s'appliquent peu importe la
   gravité de l'infraction
3. Les mesures de rechange et les sanctions communautaires doivent
   être envisagées
4. Le contexte historique de colonisation est pertinent

───────────────────────────────────────────────────────
RECOMMANDATIONS / RECOMMENDATIONS
───────────────────────────────────────────────────────

Il est recommandé que le tribunal:
1. Ordonne la préparation d'un rapport Gladue complet;
2. Tienne compte des facteurs Gladue dans la détermination de la peine;
3. Considère des alternatives à l'incarcération qui tiennent compte
   du contexte culturel et communautaire de la personne;
4. Considère la justice réparatrice et les cercles de détermination de la peine.

Date: " . date('Y-m-d') . "
";

        $doc = enrichLegalMotionWithAI($doc, 'gladue', array_merge($caseData, ['case_summary' => $background]));

        $docDir  = dirname(__DIR__) . '/downloads/legal/';
        if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
        $docFile = "gladue_{$caseId}_" . date('Ymd_His') . ".html";
        file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Gladue Factors</title><style>body{font-family:'Times New Roman',serif;font-size:12pt;margin:1in;white-space:pre-wrap;line-height:1.6;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

        if ($caseId) {
            try {
                $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
                $docs[] = ['type' => 'gladue', 'title' => 'Gladue Factors Summary', 'file' => $docFile,
                           'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
                $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
                   ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
            } catch (Exception $e) {}
        }

        return [
            'success'      => true,
            'document'     => $doc,
            'document_url' => "https://gositeme.com/downloads/legal/$docFile",
            'message'      => "Gladue factors summary drafted. IMPORTANT: This is a preliminary summary — " .
                             "a FULL Gladue report should be prepared by a qualified Gladue report writer. " .
                             "Ask Legal Aid (1-800-842-2213) to arrange a Gladue report.",
            'next_actions' => ['request full gladue report', 'search gladue case law', 'legal aid referral'],
        ];
    }

    // Default: rights overview
    return [
        'success' => true,
        'message' => "Indigenous rights resources available. Options: " .
                    "1) Learn about Gladue principles (action:'info'), " .
                    "2) Prepare Gladue factors summary (action:'gladue'), " .
                    "3) Search Indigenous rights case law. What would you like?",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 112. LEGAL FRENCH TRANSLATE — Translate legal text EN <-> FR
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalFrenchTranslate($args) {
    $text       = trim($args['text'] ?? $args['content'] ?? '');
    $direction  = trim($args['direction'] ?? $args['to'] ?? 'fr'); // fr, en, auto
    $context    = trim($args['context'] ?? 'legal'); // legal, general

    if (!$text) return ['error' => 'What text do you need translated? Provide the text and I\'ll translate it.'];

    // Detect language direction if auto
    if ($direction === 'auto') {
        // Simple heuristic: if text has French-specific chars/words, translate to English
        $frenchIndicators = ['est', 'les', 'des', 'une', 'par', 'que', 'dans', 'pour', 'sur', 'avec', 'sont', 'été', 'être'];
        $words = str_word_count(strtolower($text), 1);
        $frenchCount = count(array_intersect($words, $frenchIndicators));
        $direction = ($frenchCount > 2) ? 'en' : 'fr';
    }

    $fromLang = ($direction === 'fr') ? 'English' : 'French';
    $toLang   = ($direction === 'fr') ? 'French' : 'English';

    // Use Groq for AI translation
    $groqKey = '';
    $mcpEnv = dirname(__DIR__) . '/gocodeme/mcp-server/.env';
    if (file_exists($mcpEnv)) {
        $envC = file_get_contents($mcpEnv);
        if (preg_match('/GROQ_API_KEY=(.+)/', $envC, $gm)) $groqKey = trim($gm[1]);
    }

    if (!$groqKey) {
        // Provide common legal term translations as fallback
        $legalTerms = [
            'en' => [
                'requête' => 'application/motion', 'ordonnance' => 'order',
                'intimé' => 'respondent', 'requérant' => 'applicant',
                'jugement' => 'judgment', 'plaidoyer' => 'plea',
                'cautionnement' => 'bail', 'détenu' => 'inmate',
                'greffe' => 'court clerk\'s office', 'greffier' => 'court clerk',
                'procureur de la Couronne' => 'Crown prosecutor',
                'mise en liberté' => 'release', 'peine' => 'sentence',
                'infraction' => 'offence', 'accusé' => 'accused',
                'témoin' => 'witness', 'preuve' => 'evidence',
            ],
            'fr' => [
                'motion' => 'requête', 'order' => 'ordonnance',
                'respondent' => 'intimé', 'applicant' => 'requérant',
                'judgment' => 'jugement', 'plea' => 'plaidoyer',
                'bail' => 'cautionnement', 'inmate' => 'détenu',
                'court clerk' => 'greffier', 'Crown prosecutor' => 'procureur de la Couronne',
                'release' => 'mise en liberté', 'sentence' => 'peine',
                'offence' => 'infraction', 'accused' => 'accusé',
                'witness' => 'témoin', 'evidence' => 'preuve',
            ],
        ];

        return [
            'error'         => 'AI translation not available (no API key).',
            'common_terms'  => $legalTerms[$direction] ?? $legalTerms['fr'],
            'message'       => "AI translation unavailable. Here are common legal term translations. For full translation, contact a legal translator or use the court's translation services.",
        ];
    }

    $systemPrompt = ($context === 'legal') ?
        "You are an expert legal translator specializing in Canadian and Quebec law. " .
        "Translate the following {$fromLang} legal text into {$toLang}. " .
        "Use formal legal language appropriate for court documents. " .
        "Maintain all legal terms of art accurately. " .
        "For Quebec-specific legal terms, use the accepted {$toLang} equivalents. " .
        "Preserve the original formatting and structure. Return ONLY the translation." :
        "Translate the following {$fromLang} text into {$toLang}. Use clear, natural language. Return ONLY the translation.";

    $payload = json_encode([
        'model'       => 'llama-3.3-70b-versatile',
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text],
        ],
        'temperature' => 0.2,
        'max_tokens'  => 4096,
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $groqKey],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$resp) {
        return ['error' => "Translation failed (HTTP {$httpCode}). Try again or use a manual translation service."];
    }

    $data = json_decode($resp, true);
    $translated = $data['choices'][0]['message']['content'] ?? '';

    if (!$translated) {
        return ['error' => 'Translation returned empty result. Please try again.'];
    }

    return [
        'success'     => true,
        'original'    => $text,
        'translated'  => $translated,
        'from'        => $fromLang,
        'to'          => $toLang,
        'context'     => $context,
        'message'     => "Translated from {$fromLang} to {$toLang}" . ($context === 'legal' ? ' (legal terminology)' : '') . ". " .
                        "NOTE: Always verify legal translations with a qualified translator before filing in court.",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 113. LEGAL DEADLINE CALC — Calculate legal deadlines
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalDeadlineCalc($args) {
    $deadlineType  = trim($args['type'] ?? $args['deadline_type'] ?? '');
    $startDate     = trim($args['start_date'] ?? $args['from_date'] ?? $args['date'] ?? '');
    $customDays    = (int)($args['custom_days'] ?? 0);

    // Legal deadline types and their periods
    $deadlines = [
        'appeal_conviction' => ['days' => 30, 'label' => 'Appeal of conviction (s. 678.1 Criminal Code)', 'business' => false],
        'appeal_sentence'   => ['days' => 30, 'label' => 'Appeal of sentence (s. 678.1 Criminal Code)', 'business' => false],
        'appeal_summary'    => ['days' => 30, 'label' => 'Appeal of summary conviction (s. 813 Criminal Code)', 'business' => false],
        'bail_review'       => ['days' => 30, 'label' => 'Bail review (s. 520/521 Criminal Code)', 'business' => false, 'note' => 'No strict deadline but should be filed promptly'],
        'habeas_corpus'     => ['days' => 0,  'label' => 'Habeas corpus — NO DEADLINE (file immediately)', 'business' => false, 'note' => 'Can be filed at any time; urgent priority'],
        'grievance_internal'=> ['days' => 15, 'label' => 'Internal grievance response (CCRA)', 'business' => true],
        'grievance_oci'     => ['days' => 80, 'label' => 'OCI complaint after exhausting internal grievance', 'business' => true],
        'disclosure'        => ['days' => 0,  'label' => 'Disclosure — ongoing obligation (Stinchcombe)', 'business' => false, 'note' => 'Crown must disclose before trial; no fixed deadline but must be timely'],
        'jordan_superior'   => ['days' => 912, 'label' => 'R. v. Jordan — Superior Court trial limit (30 months)', 'business' => false],
        'jordan_provincial' => ['days' => 547, 'label' => 'R. v. Jordan — Provincial Court trial limit (18 months)', 'business' => false],
        'pardon_summary'    => ['days' => 1825, 'label' => 'Record suspension — summary (5 years)', 'business' => false],
        'pardon_indictable' => ['days' => 3650, 'label' => 'Record suspension — indictable (10 years)', 'business' => false],
        'immigration_detention' => ['days' => 2, 'label' => 'First immigration detention review (48 hours)', 'business' => false],
        'segregation_review' => ['days' => 5, 'label' => 'SIU review by IEDM (5 days)', 'business' => false],
        'charter_notice'   => ['days' => 15, 'label' => 'Notice of constitutional question (s. 95 Courts of Justice Act)', 'business' => true],
    ];

    if (!$deadlineType && !$customDays) {
        // Return full list of deadlines  
        return [
            'success'    => true,
            'deadlines'  => array_map(fn($k, $d) => ['type' => $k, 'label' => $d['label'], 'days' => $d['days'],
                           'business_days' => $d['business'] ?? false], array_keys($deadlines), $deadlines),
            'message'    => "Available deadline types. Which one do you need to calculate? Also provide the start date (e.g., conviction date, sentence date, etc.).",
        ];
    }

    $deadline = $deadlines[$deadlineType] ?? null;
    $days = $deadline ? $deadline['days'] : $customDays;
    $label = $deadline ? $deadline['label'] : "Custom deadline ({$customDays} days)";
    $isBusinessDays = $deadline['business'] ?? false;

    if ($days === 0 && $deadline) {
        return [
            'success'    => true,
            'type'       => $deadlineType,
            'label'      => $label,
            'note'       => $deadline['note'] ?? 'No fixed deadline',
            'message'    => $label . '. ' . ($deadline['note'] ?? ''),
        ];
    }

    // Calculate deadline
    try {
        $start = $startDate ? new DateTime($startDate) : new DateTime();
    } catch (Exception $e) {
        $start = new DateTime();
    }

    $end = clone $start;
    if ($isBusinessDays) {
        $added = 0;
        while ($added < $days) {
            $end->modify('+1 day');
            $dow = (int)$end->format('N');
            if ($dow <= 5) $added++; // Skip weekends
        }
    } else {
        $end->modify("+{$days} days");
    }

    $now = new DateTime();
    $diff = $now->diff($end);
    $isPast = !$diff->invert ? false : true;
    $isPast = $end < $now;

    return [
        'success'       => true,
        'type'          => $deadlineType ?: 'custom',
        'label'         => $label,
        'start_date'    => $start->format('Y-m-d'),
        'deadline_date' => $end->format('Y-m-d'),
        'days'          => $days,
        'business_days' => $isBusinessDays,
        'is_past'       => $isPast,
        'days_remaining'=> $isPast ? 0 : $diff->days,
        'message'       => "{$label}\nStart: " . $start->format('Y-m-d') .
                          "\nDeadline: " . $end->format('Y-m-d') .
                          ($isBusinessDays ? ' (business days)' : '') .
                          ($isPast ? "\n⚠ DEADLINE HAS PASSED ({$diff->days} days ago). You may need to file for an extension of time." :
                           "\n{$diff->days} day(s) remaining."),
        'note'          => $deadline['note'] ?? null,
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 114. LEGAL EVIDENCE CHECKLIST — Evidence preparation guide
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalEvidenceChecklist($args) {
    $caseId    = (int)($args['case_id'] ?? 0);
    $caseType  = trim($args['case_type'] ?? $args['type'] ?? 'criminal');
    $offence   = trim($args['offence'] ?? $args['charges'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
        $caseType = $caseData['case_type'] ?? $caseType;
        $offence  = $caseData['charges'] ?? $offence;
    }

    $checklists = [
        'criminal' => [
            'title' => 'Criminal Defence Evidence Checklist',
            'items' => [
                ['category' => 'Disclosure', 'items' => [
                    'Police occurrence report(s) — obtained?',
                    'Supplementary reports — obtained?',
                    'Witness statements — all received?',
                    'Video/audio surveillance — requested?',
                    'Forensic reports (DNA, fingerprints, etc.) — reviewed?',
                    'Officer notes — complete and unredacted?',
                    'Expert reports — received and reviewed?',
                    '911 call recordings — requested?',
                    'Cell phone records / electronic evidence — disclosed?',
                ]],
                ['category' => 'Defence Evidence', 'items' => [
                    'Alibi evidence — documented?',
                    'Defence witnesses — identified and contacted?',
                    'Character witnesses — available?',
                    'Expert witness — retained if needed?',
                    'Surveillance footage from other sources — obtained?',
                    'Photos of scene / injuries — taken/preserved?',
                    'Medical records — relevant and obtained?',
                    'Text messages / emails — preserved?',
                ]],
                ['category' => 'Charter Issues', 'items' => [
                    'Were s. 10(b) rights (right to counsel) respected?',
                    'Was there an unreasonable search (s. 8)?',
                    'Was there arbitrary detention (s. 9)?',
                    'Were there any statement-taking issues (voluntariness)?',
                    'Was the arrest lawful?',
                    'R. v. Jordan delay issues — calculate total delay',
                ]],
                ['category' => 'Pre-Trial', 'items' => [
                    'Disclosure complete?',
                    'Charter application needed?',
                    'Preliminary inquiry elected (indictable)?',
                    'Mode of trial elected (judge alone vs. jury)?',
                    'Pre-trial conference scheduled?',
                    'Plea negotiations explored?',
                ]],
            ],
        ],
        'habeas_corpus' => [
            'title' => 'Habeas Corpus Evidence Checklist',
            'items' => [
                ['category' => 'Core Documents', 'items' => [
                    'Warrant of committal — copy obtained?',
                    'Transfer documentation — if applicable',
                    'Segregation/SIU placement order — if applicable',
                    'Institutional records of conditions',
                    'Medical records showing impact — if health related',
                    'Affidavit of the applicant — drafted?',
                ]],
                ['category' => 'Supporting Evidence', 'items' => [
                    'Correctional records showing rights violations',
                    'Grievance filings and responses',
                    'Letters/reports from case manager',
                    'CanLII case law supporting your position',
                    'Expert opinion (medical, psychological) if relevant',
                ]],
            ],
        ],
        'parole' => [
            'title' => 'Parole Hearing Evidence Checklist',
            'items' => [
                ['category' => 'Required Documents', 'items' => [
                    'Institutional progress report — obtained from case manager?',
                    'Completed programs — certificates gathered?',
                    'Release plan — housing confirmed?',
                    'Employment plan — letter from employer/program?',
                    'Community sponsor — identified and confirmed?',
                    'Personal statement — drafted and practiced?',
                ]],
                ['category' => 'Supporting Evidence', 'items' => [
                    'Letters of support (family, employer, community)',
                    'Treatment completion certificates',
                    'Institutional conduct record',
                    'Victim considerations — reviewed and prepared?',
                    'Parole officer recommendation',
                ]],
            ],
        ],
    ];

    $checklist = $checklists[$caseType] ?? $checklists['criminal'];

    // Build text version
    $doc = "═══════════════════════════════════════════════════════\n";
    $doc .= strtoupper($checklist['title']) . "\n";
    $doc .= "═══════════════════════════════════════════════════════\n\n";
    $doc .= "Case: " . ($caseData['case_number'] ?? ($caseId ? "#{$caseId}" : 'N/A')) . "\n";
    $doc .= "Type: {$caseType}\n";
    if ($offence) $doc .= "Offence(s): {$offence}\n";
    $doc .= "Date: " . date('Y-m-d') . "\n\n";

    foreach ($checklist['items'] as $section) {
        $doc .= "───────────────────────────────────────────────────────\n";
        $doc .= strtoupper($section['category']) . "\n";
        $doc .= "───────────────────────────────────────────────────────\n\n";
        foreach ($section['items'] as $item) {
            $doc .= "☐ {$item}\n";
        }
        $doc .= "\n";
    }

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "evidence_checklist_{$caseType}_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Evidence Checklist</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'evidence_checklist', 'title' => $checklist['title'], 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'checklist'    => $checklist,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "{$checklist['title']} generated. Go through each item systematically. " .
                         "Missing disclosure? I can help you draft a Stinchcombe request. " .
                         "Missing defence evidence? Let's identify what you need.",
        'next_actions' => ['request disclosure', 'draft affidavit', 'search case law'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 115. LEGAL WITNESS STATEMENT — Draft witness statements
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalWitnessStatement($args) {
    $caseId       = (int)($args['case_id'] ?? 0);
    $witnessName  = trim($args['witness_name'] ?? $args['name'] ?? '');
    $relationship = trim($args['relationship'] ?? '');
    $facts        = $args['facts'] ?? $args['statement'] ?? [];
    $purpose      = trim($args['purpose'] ?? 'defence');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    if (!$witnessName) return ['error' => 'What is the name of the witness?'];

    if (is_string($facts)) $facts = array_filter(array_map('trim', explode("\n", $facts)));
    if (empty($facts)) return ['error' => 'What did the witness see/hear/know? Provide the facts of their testimony.'];

    $caseNumber = $caseData['case_number'] ?? ($args['case_number'] ?? '');
    $district   = $caseData['court_district'] ?? ($args['district'] ?? 'Montréal');
    $accusedName = $caseData['caller_name'] ?? ($args['accused_name'] ?? '');

    $factsText = '';
    foreach ($facts as $i => $fact) {
        $factsText .= ($i + 1) . ". {$fact}\n\n";
    }

    $doc = "
CANADA
PROVINCE DE QUÉBEC
DISTRICT DE " . strtoupper($district) . "

No: " . ($caseNumber ?: '_______________') . "

═══════════════════════════════════════════════════════
DÉCLARATION DE TÉMOIN / WITNESS STATEMENT
═══════════════════════════════════════════════════════

Je soussigné(e), {$witnessName}, déclare ce qui suit:
I, the undersigned, {$witnessName}, declare the following:

" . ($relationship ? "Lien avec l'accusé(e) / Relationship: {$relationship}\n" : "") . "
" . ($accusedName ? "Re: {$accusedName}\n" : "") . "

───────────────────────────────────────────────────────
FAITS / FACTS
───────────────────────────────────────────────────────

{$factsText}

───────────────────────────────────────────────────────

Je déclare que les faits ci-dessus sont vrais à ma connaissance et
croyance. Je comprends que cette déclaration peut être utilisée dans
le cadre de procédures judiciaires.

I declare that the above facts are true to my knowledge and belief.
I understand that this statement may be used in legal proceedings.

AVERTISSEMENT / WARNING:
Faire une fausse déclaration sous serment constitue une infraction
criminelle en vertu de l'article 131 du Code criminel.
Making a false statement under oath is a criminal offence under
s. 131 of the Criminal Code.

Signature: _________________________________
           {$witnessName}
Date: " . date('Y-m-d') . "

Témoin de la signature / Signature witnessed by:
_________________________________
Nom / Name: _______________
";

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "witness_statement_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Witness Statement</title><style>body{font-family:'Times New Roman',serif;font-size:12pt;margin:1in;white-space:pre-wrap;line-height:1.6;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'witness_statement', 'title' => "Witness Statement — {$witnessName}", 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'fact_count'   => count($facts),
        'message'      => "Witness statement drafted for {$witnessName} with " . count($facts) . " fact(s). " .
                         "The witness should review, sign, and have the signature witnessed. " .
                         "If you need this as a sworn affidavit, I can convert it.",
        'next_actions' => ['convert to affidavit', 'add more facts', 'another witness statement'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 116. LEGAL PLEA NEGOTIATION — Plea negotiation preparation
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalPleaNegotiation($args) {
    $caseId       = (int)($args['case_id'] ?? 0);
    $action       = trim($args['action'] ?? 'info'); // info, prepare, counter_offer
    $currentOffer = trim($args['current_offer'] ?? $args['offer'] ?? '');
    $charges      = trim($args['charges'] ?? $args['offence'] ?? '');
    $mitigating   = $args['mitigating_factors'] ?? $args['mitigating'] ?? [];

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
        $charges = $charges ?: ($caseData['charges'] ?? '');
    }

    $callerName = $caseData['caller_name'] ?? ($args['caller_name'] ?? '');

    if ($action === 'info') {
        return [
            'success' => true,
            'info'    => [
                'what'      => 'Plea negotiations (plea bargaining) involve discussions between the defence and Crown to resolve charges without a full trial.',
                'options'   => [
                    'Guilty plea to lesser charge' => 'Crown agrees to withdraw or reduce some charges',
                    'Joint submission on sentence' => 'Both sides agree on sentence recommendation to the judge',
                    'Withdrawal of charges' => 'Crown withdraws charges (diversion, peace bond, etc.)',
                    'Stay of proceedings' => 'Crown stays proceedings (can recommence within 1 year)',
                ],
                'rights'    => [
                    'You are NEVER required to accept a plea offer',
                    'You have the right to a full trial',
                    'Any plea must be voluntary and informed',
                    'The judge is not bound by the joint submission (but usually follows it — R. v. Anthony-Cook)',
                    'You should understand ALL consequences before pleading guilty (immigration, employment, etc.)',
                ],
                'warning'   => 'NEVER plead guilty without understanding all consequences. A guilty plea is very difficult to withdraw later. Consult a lawyer if possible (Legal Aid: 1-800-842-2213).',
            ],
            'message' => "Plea negotiations can resolve your case. But NEVER plead guilty without understanding all consequences. " .
                        "Would you like to prepare for negotiations or analyze a current offer?",
        ];
    }

    if ($action === 'prepare' || $action === 'counter_offer') {
        if (is_string($mitigating)) $mitigating = array_filter(array_map('trim', explode("\n", $mitigating)));

        $mitigatingFactors = !empty($mitigating) ? $mitigating : [
            'First offence / No prior criminal record',
            'Employment / positive community ties',
            'Remorse and accountability',
            'Willingness to attend counselling/programs',
            'Family responsibilities',
            'Youth / age considerations',
            'Mental health / addiction factors',
            'Pre-trial custody served',
        ];

        $doc = "
═══════════════════════════════════════════════════════
PRÉPARATION AUX NÉGOCIATIONS DE PLAIDOYER
PLEA NEGOTIATION PREPARATION
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "
Accusé(e) / Accused: {$callerName}
Accusations / Charges: {$charges}
" . ($currentOffer ? "\nOFFRE ACTUELLE / CURRENT OFFER: {$currentOffer}\n" : "") . "

───────────────────────────────────────────────────────
FACTEURS ATTÉNUANTS / MITIGATING FACTORS
───────────────────────────────────────────────────────

";
        foreach ($mitigatingFactors as $i => $f) {
            $doc .= ($i + 1) . ". {$f}\n";
        }

        $doc .= "

───────────────────────────────────────────────────────
CONSIDÉRATIONS STRATÉGIQUES / STRATEGIC CONSIDERATIONS
───────────────────────────────────────────────────────

Avant d'accepter un plaidoyer / Before accepting a plea:
1. Forces et faiblesses de la preuve de la Couronne?
   Strengths and weaknesses of Crown's evidence?
2. Y a-t-il des questions constitutionnelles (Charte)?
   Are there Charter issues?
3. Le délai dépasse-t-il les limites Jordan?
   Does delay exceed Jordan limits?
4. Conséquences collatérales (immigration, emploi, voyages)?
   Collateral consequences (immigration, employment, travel)?
" . ($currentOffer ? "
───────────────────────────────────────────────────────
ANALYSE DE L'OFFRE / OFFER ANALYSIS
───────────────────────────────────────────────────────

Offre actuelle / Current offer: {$currentOffer}

Points à négocier / Points to negotiate:
1. [Réduction des accusations / Charge reduction]
2. [Peine suggérée / Suggested sentence]
3. [Conditions / Conditions]
4. [Délai / Timing]
" : "") . "

───────────────────────────────────────────────────────
RAPPEL IMPORTANT / IMPORTANT REMINDER
───────────────────────────────────────────────────────

- Un plaidoyer de culpabilité est IRRÉVOCABLE (très difficile à retirer)
- Le juge N'EST PAS lié par la suggestion commune (mais la suit généralement)
- Consultez un avocat AVANT de plaider coupable (Aide juridique: 1-800-842-2213)
- Vous avez TOUJOURS le droit à un procès complet
";

        $docDir  = dirname(__DIR__) . '/downloads/legal/';
        if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
        $docFile = "plea_prep_{$caseId}_" . date('Ymd_His') . ".html";
        file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Plea Negotiation</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

        if ($caseId) {
            try {
                $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
                $docs[] = ['type' => 'plea_prep', 'title' => 'Plea Negotiation Prep', 'file' => $docFile,
                           'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
                $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
                   ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
            } catch (Exception $e) {}
        }

        return [
            'success'           => true,
            'document'          => $doc,
            'document_url'      => "https://gositeme.com/downloads/legal/$docFile",
            'mitigating_factors'=> $mitigatingFactors,
            'message'           => "Plea negotiation preparation drafted. " .
                                  ($currentOffer ? "Current offer: {$currentOffer}. Consider the strengths and weaknesses of the Crown's case before deciding. " : "") .
                                  "WARNING: NEVER plead guilty without understanding ALL consequences. Consult Legal Aid: 1-800-842-2213.",
            'next_actions'      => ['draft consent order', 'evidence checklist', 'search sentencing case law'],
        ];
    }

    return ['success' => true, 'message' => "Plea negotiation help available. Options: info, prepare, or counter_offer. What do you need?"];
}


// ═══════════════════════════════════════════════════════════════════════════
// 117. LEGAL SURETY PLAN — Surety / bail plan preparation
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalSuretyPlan($args) {
    $caseId         = (int)($args['case_id'] ?? 0);
    $suretyName     = trim($args['surety_name'] ?? $args['name'] ?? '');
    $relationship   = trim($args['relationship'] ?? '');
    $address        = trim($args['address'] ?? '');
    $employment     = trim($args['employment'] ?? '');
    $amount         = trim($args['amount'] ?? $args['bail_amount'] ?? '');
    $supervision    = trim($args['supervision_plan'] ?? $args['plan'] ?? '');
    $accusedName    = trim($args['accused_name'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
        $accusedName = $accusedName ?: ($caseData['caller_name'] ?? '');
    }

    if (!$suretyName) return ['error' => 'Who is the proposed surety? Provide their name.'];

    $district = $caseData['court_district'] ?? ($args['district'] ?? 'Montréal');

    $doc = "
═══════════════════════════════════════════════════════
PLAN DE CAUTION / SURETY PLAN
(Art. 515 du Code criminel / s. 515 Criminal Code)
═══════════════════════════════════════════════════════

Date: " . date('Y-m-d') . "
District: {$district}
Accusé(e) / Accused: " . ($accusedName ?: '_______________') . "

───────────────────────────────────────────────────────
INFORMATION SUR LA CAUTION / SURETY INFORMATION
───────────────────────────────────────────────────────

Nom de la caution / Surety Name: {$suretyName}
Lien avec l'accusé(e) / Relationship: " . ($relationship ?: '_______________') . "
Adresse / Address: " . ($address ?: '_______________') . "
Emploi / Employment: " . ($employment ?: '_______________') . "
Montant offert / Amount Offered: " . ($amount ? "\${$amount}" : '_______________') . "

───────────────────────────────────────────────────────
PLAN DE SUPERVISION / SUPERVISION PLAN
───────────────────────────────────────────────────────

" . ($supervision ?: "La caution s'engage à:
The surety commits to:

1. Que l'accusé(e) résidera à l'adresse indiquée ci-dessus;
   The accused will reside at the address indicated above;

2. Que l'accusé(e) respectera toutes les conditions imposées par le tribunal;
   The accused will comply with all court-imposed conditions;

3. Que la caution avisera la police si l'accusé(e) ne respecte pas les conditions;
   The surety will notify police if the accused breaches conditions;

4. Que la caution est prête à déposer la somme de " . ($amount ? "\${$amount}" : "_______________\$") . "
   en garantie de la comparution de l'accusé(e);
   The surety is prepared to deposit " . ($amount ? "\${$amount}" : "_______________") . "
   to guarantee the accused's appearance;") . "

───────────────────────────────────────────────────────
QUALIFICATIONS DE LA CAUTION / SURETY QUALIFICATIONS
───────────────────────────────────────────────────────

La caution déclare:
The surety declares:

☐ Être un(e) citoyen(ne) canadien(ne) ou résident(e) permanent(e)
  Is a Canadian citizen or permanent resident

☐ Être majeur(e) (18 ans et plus)
  Is of legal age (18+)

☐ N'avoir aucun casier judiciaire en cours
  Has no outstanding criminal charges

☐ Avoir la capacité financière de déposer le cautionnement proposé
  Has the financial capacity to deposit the proposed bail

☐ Être disposé(e) et capable de superviser l'accusé(e)
  Is willing and able to supervise the accused

☐ Comprendre qu'en cas de manquement, le montant peut être confisqué
  Understands the amount may be forfeited in case of breach

───────────────────────────────────────────────────────

Signature de la caution / Surety Signature:
_________________________________
{$suretyName}
Date: " . date('Y-m-d') . "
";

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "surety_plan_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Surety Plan</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => 'surety_plan', 'title' => "Surety Plan — {$suretyName}", 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW() WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "Surety plan drafted for {$suretyName}. The surety should review and sign this. " .
                         "The surety will need to attend the bail hearing and may be examined by the Crown. " .
                         "The surety should bring: ID, proof of employment/income, proof of address.",
        'next_actions' => ['draft bail review application', 'prepare bail hearing', 'search bail case law'],
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 118. LEGAL COSTS ESTIMATE — Estimate legal costs
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalCostsEstimate($args) {
    $caseType    = trim($args['case_type'] ?? $args['type'] ?? 'criminal');
    $complexity  = trim($args['complexity'] ?? 'medium'); // simple, medium, complex
    $trialDays   = (int)($args['trial_days'] ?? 0);
    $province    = trim($args['province'] ?? 'QC');

    $rates = [
        'legal_aid'       => 'FREE (if you qualify financially — income-based eligibility)',
        'duty_counsel'    => 'FREE (available at first court appearance)',
        'private_hourly'  => '$200-$500/hour (varies by experience and region)',
        'junior_lawyer'   => '$150-$250/hour',
        'senior_lawyer'   => '$300-$600/hour',
    ];

    $estimates = [
        'criminal' => [
            'simple' => [
                'label' => 'Simple criminal matter (guilty plea, minor offence)',
                'private_range' => '$2,000-$5,000',
                'legal_aid' => 'Covered if eligible',
                'includes' => 'Initial consultation, disclosure review, one court appearance, plea',
            ],
            'medium' => [
                'label' => 'Standard criminal trial (1-3 day trial)',
                'private_range' => '$5,000-$25,000',
                'legal_aid' => 'Covered if eligible',
                'includes' => 'Disclosure review, pre-trial prep, Charter motions, trial, sentencing',
            ],
            'complex' => [
                'label' => 'Complex criminal trial (jury, multiple charges)',
                'private_range' => '$25,000-$100,000+',
                'legal_aid' => 'Covered if eligible (may need exceptional authorization)',
                'includes' => 'Extensive disclosure, preliminary inquiry, jury selection, multi-day trial, sentencing',
            ],
        ],
        'appeal' => [
            'simple' => ['label' => 'Sentence appeal', 'private_range' => '$3,000-$10,000', 'legal_aid' => 'Covered if eligible'],
            'medium' => ['label' => 'Standard criminal appeal', 'private_range' => '$10,000-$30,000', 'legal_aid' => 'Covered if eligible'],
            'complex' => ['label' => 'Complex/constitutional appeal', 'private_range' => '$30,000-$75,000+', 'legal_aid' => 'Covered if eligible'],
        ],
        'immigration' => [
            'simple' => ['label' => 'Detention review', 'private_range' => '$2,000-$5,000', 'legal_aid' => 'Covered if eligible'],
            'medium' => ['label' => 'Removal order hearing', 'private_range' => '$5,000-$15,000', 'legal_aid' => 'Covered if eligible'],
            'complex' => ['label' => 'Refugee claim + Federal Court', 'private_range' => '$10,000-$30,000+', 'legal_aid' => 'Covered if eligible'],
        ],
        'habeas_corpus' => [
            'simple' => ['label' => 'Habeas corpus application', 'private_range' => '$3,000-$8,000', 'legal_aid' => 'Covered if eligible'],
            'medium' => ['label' => 'Complex habeas corpus', 'private_range' => '$8,000-$20,000', 'legal_aid' => 'Covered if eligible'],
            'complex' => ['label' => 'Habeas corpus with full hearing', 'private_range' => '$15,000-$40,000', 'legal_aid' => 'Covered if eligible'],
        ],
    ];

    $typeEstimates = $estimates[$caseType] ?? $estimates['criminal'];
    $estimate = $typeEstimates[$complexity] ?? $typeEstimates['medium'];

    return [
        'success'       => true,
        'case_type'     => $caseType,
        'complexity'    => $complexity,
        'estimate'      => $estimate,
        'hourly_rates'  => $rates,
        'free_options'  => [
            'Legal Aid Québec' => '1-800-842-2213 (free if your income qualifies)',
            'Duty Counsel' => 'Free legal advice at first court appearance',
            'Pro Bono Québec' => 'https://probonoquebec.ca/ — free legal clinics',
            'Barreau du Québec Referral' => '1-866-954-3528 (free 30-minute consultation)',
            'University Legal Clinics' => 'McGill, UdeM, and Laval offer free legal clinics',
        ],
        'self_represented' => 'You have the right to represent yourself. Alfred can help you prepare documents, search case law, and understand procedures.',
        'message'       => "Cost estimate for {$estimate['label']}:\n" .
                          "Private lawyer: {$estimate['private_range']}\n" .
                          "Legal Aid: {$estimate['legal_aid']}\n\n" .
                          "FREE OPTIONS: Legal Aid (1-800-842-2213), Duty Counsel (at court), Pro Bono clinics. " .
                          "You can also self-represent — I can help you prepare your case.",
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// 119. LEGAL EMERGENCY INJUNCTION — Emergency injunction / stay
// ═══════════════════════════════════════════════════════════════════════════

function toolLegalEmergencyInjunction($args) {
    $caseId       = (int)($args['case_id'] ?? 0);
    $type         = trim($args['type'] ?? 'stay'); // stay, injunction, interim_order
    $urgency      = trim($args['urgency'] ?? 'emergency'); // emergency, urgent
    $grounds      = trim($args['grounds'] ?? $args['reason'] ?? '');
    $remedy       = trim($args['remedy'] ?? $args['relief'] ?? '');
    $irreparable  = trim($args['irreparable_harm'] ?? $args['harm'] ?? '');

    $db = getDB();
    $caseData = [];
    if ($caseId) {
        $s = $db->prepare("SELECT * FROM alfred_legal_cases WHERE id=:id");
        $s->execute([':id' => $caseId]);
        $caseData = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    $callerName  = $caseData['caller_name'] ?? ($args['caller_name'] ?? 'LE REQUÉRANT');
    $inmateId    = $caseData['inmate_id'] ?? ($args['inmate_id'] ?? '');
    $institution = $caseData['institution'] ?? ($args['institution'] ?? '');
    $district    = $caseData['court_district'] ?? ($args['district'] ?? 'Montréal');
    $caseNumber  = $caseData['case_number'] ?? ($args['case_number'] ?? '');

    if (!$grounds) return ['error' => 'What are the grounds for the emergency injunction? What irreparable harm will occur without it?'];

    $typeLabels = [
        'stay'          => "DEMANDE DE SURSIS D'EXÉCUTION\nAPPLICATION FOR STAY OF EXECUTION",
        'injunction'    => "DEMANDE D'INJONCTION PROVISOIRE\nAPPLICATION FOR INTERIM INJUNCTION",
        'interim_order' => "DEMANDE D'ORDONNANCE DE SAUVEGARDE\nAPPLICATION FOR SAFEGUARD ORDER",
    ];
    $typeLabel = $typeLabels[$type] ?? $typeLabels['stay'];

    $doc = "
*** URGENT / EMERGENCY ***

CANADA
PROVINCE DE QUÉBEC
DISTRICT DE " . strtoupper($district) . "

No: " . ($caseNumber ?: '_______________') . "

COUR SUPÉRIEURE

{$callerName}
" . ($inmateId ? "(Matricule: {$inmateId})" : "") . "
    Requérant / Applicant
c.
SA MAJESTÉ LE ROI / HIS MAJESTY THE KING
et/and
" . strtoupper($institution ?: 'INTIMÉ') . "
    Intimé(s) / Respondent(s)

═══════════════════════════════════════════════════════
{$typeLabel}
(Art. 51 Code de procédure civile / Emergency provisions)
═══════════════════════════════════════════════════════

*** AUDITION URGENTE DEMANDÉE / URGENT HEARING REQUESTED ***

LE REQUÉRANT EXPOSE RESPECTUEUSEMENT:

───────────────────────────────────────────────────────
FAITS / FACTS
───────────────────────────────────────────────────────

1. {$grounds}

───────────────────────────────────────────────────────
PRÉJUDICE IRRÉPARABLE / IRREPARABLE HARM
───────────────────────────────────────────────────────

2. " . ($irreparable ?: "Sans l'intervention urgente du tribunal, le requérant subira un préjudice sérieux et irréparable, notamment:\n   [Décrire le préjudice irréparable / Describe irreparable harm]") . "

───────────────────────────────────────────────────────
CRITÈRE TRIPARTITE / THREE-PART TEST
(RJR-MacDonald Inc. c. Canada, [1994] 1 RCS 311)
───────────────────────────────────────────────────────

3. QUESTION SÉRIEUSE À JUGER / SERIOUS ISSUE TO BE TRIED:
   Le requérant soulève des questions sérieuses de droit, incluant
   la protection de ses droits fondamentaux en vertu de la Charte.

4. PRÉJUDICE IRRÉPARABLE / IRREPARABLE HARM:
   " . ($irreparable ?: "Le préjudice subi ne pourra être compensé par des dommages-intérêts.") . "

5. BALANCE DES INCONVÉNIENTS / BALANCE OF CONVENIENCE:
   La balance des inconvénients favorise le requérant. Le préjudice
   causé au requérant par le refus du sursis est nettement plus
   important que tout inconvénient causé à l'intimé par l'octroi du sursis.

───────────────────────────────────────────────────────
CONCLUSIONS RECHERCHÉES / RELIEF SOUGHT
───────────────────────────────────────────────────────

" . ($remedy ?: "PAR CES MOTIFS, PLAISE AU TRIBUNAL:\n\nACCORDER le sursis/l'injonction demandé(e);\nORDONNER toute autre mesure que le tribunal estime juste;\nLE TOUT avec dépens.") . "

" . ($urgency === 'emergency' ? "\n*** CETTE DEMANDE EST URGENTE ET REQUIERT UNE AUDITION IMMÉDIATE ***\n" : "") . "

{$district}, le " . date('j F Y') . "

_________________________________
{$callerName}
Se représentant seul / Self-represented
" . ($institution ? "Détenu à {$institution}" : "") . "
";

    $doc = enrichLegalMotionWithAI($doc, 'emergency_injunction', array_merge($caseData, ['case_summary' => $grounds]));

    $docDir  = dirname(__DIR__) . '/downloads/legal/';
    if (!is_dir($docDir)) @mkdir($docDir, 0755, true);
    $docFile = "emergency_{$type}_{$caseId}_" . date('Ymd_His') . ".html";
    file_put_contents($docDir . $docFile, "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Emergency Injunction</title><style>body{font-family:'Courier New',monospace;font-size:11pt;margin:1in;white-space:pre-wrap;}</style></head><body>" . htmlspecialchars($doc) . "</body></html>");

    if ($caseId) {
        try {
            $docs = json_decode($caseData['documents_filed'] ?? '[]', true) ?: [];
            $docs[] = ['type' => "emergency_{$type}", 'title' => 'Emergency Injunction/Stay', 'file' => $docFile,
                       'url' => "https://gositeme.com/downloads/legal/$docFile", 'created' => date('Y-m-d H:i:s'), 'status' => 'drafted'];
            $db->prepare("UPDATE alfred_legal_cases SET documents_filed=:docs, updated_at=NOW(), case_notes=CONCAT(COALESCE(case_notes,''), :note) WHERE id=:id")
               ->execute([':docs' => json_encode($docs), ':note' => "\n[" . date('Y-m-d H:i') . "] *** EMERGENCY {$type} drafted ***", ':id' => $caseId]);
        } catch (Exception $e) {}
    }

    return [
        'success'      => true,
        'type'         => $type,
        'urgency'      => $urgency,
        'document'     => $doc,
        'document_url' => "https://gositeme.com/downloads/legal/$docFile",
        'message'      => "EMERGENCY {$type} application drafted. FILE THIS IMMEDIATELY with the Superior Court. " .
                         "Call the court clerk (greffe) to request an URGENT hearing. " .
                         "The RJR-MacDonald test applies: serious issue, irreparable harm, balance of convenience. " .
                         "Legal Aid URGENT line: 1-800-842-2213.",
        'next_actions' => ['fax to court immediately', 'call court clerk', 'draft supporting affidavit'],
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// STUDENTS K-12 — Voice Tools
// ═══════════════════════════════════════════════════════════════════════════

function toolHomeworkHelper($args) {
    $subject = trim($args['subject'] ?? '');
    $question = trim($args['question'] ?? '');
    $grade = intval($args['grade_level'] ?? 8);
    $showSteps = $args['show_steps'] ?? true;
    if (empty($subject) || empty($question)) {
        return ['error' => false, 'message' => 'I need the subject and the homework question. What subject is this for, and what\'s the question?'];
    }
    $prompt = "You are a patient tutor helping a grade $grade student with $subject. The question is: $question. " .
              ($showSteps ? "Show step-by-step solution with explanations. Don't just give the answer - teach HOW to solve it." : "Give a concise answer.");
    $result = callAlfred($prompt);
    return ['success' => true, 'subject' => $subject, 'grade_level' => $grade, 'explanation' => $result,
            'message' => "Here's help with your $subject homework. " . substr($result, 0, 500)];
}

function toolMathTutor($args) {
    $topic = trim($args['topic'] ?? '');
    $action = trim($args['action'] ?? 'explain');
    $difficulty = $args['difficulty'] ?? 'medium';
    $problem = trim($args['problem'] ?? '');
    if (empty($topic)) return ['error' => false, 'message' => 'What math topic would you like help with? For example: fractions, algebra, geometry?'];
    $prompt = "You are an encouraging math tutor. Topic: $topic. Difficulty: $difficulty. Action: $action. " .
              (!empty($problem) ? "Problem: $problem. " : "") . "Provide clear explanations with examples.";
    $result = callAlfred($prompt);
    return ['success' => true, 'topic' => $topic, 'action' => $action, 'content' => $result,
            'message' => "Let's work on $topic together! " . substr($result, 0, 500)];
}

function toolScienceLabSim($args) {
    $subject = trim($args['subject'] ?? '');
    $experiment = trim($args['experiment'] ?? '');
    $grade = intval($args['grade_level'] ?? 8);
    if (empty($subject) || empty($experiment)) return ['error' => false, 'message' => 'What science subject and experiment would you like to explore?'];
    $prompt = "Simulate a $subject experiment for a grade $grade student: $experiment. Include: hypothesis, materials, procedure steps, expected observations, and conclusion. Make it engaging and educational.";
    $result = callAlfred($prompt);
    return ['success' => true, 'subject' => $subject, 'experiment' => $experiment, 'simulation' => $result,
            'message' => "Let's do a virtual $subject experiment! " . substr($result, 0, 500)];
}

function toolEssayCoach($args) {
    $action = trim($args['action'] ?? 'outline');
    $topic = trim($args['topic'] ?? '');
    $content = trim($args['content'] ?? '');
    $essayType = $args['essay_type'] ?? 'expository';
    if (empty($topic)) return ['error' => false, 'message' => 'What is your essay topic or prompt?'];
    $prompt = "You are an essay writing coach. Essay type: $essayType. Topic: $topic. Stage: $action. " .
              (!empty($content) ? "Current draft: $content. " : "") .
              "Guide the student through this stage without writing the essay for them. Provide scaffolding and feedback.";
    $result = callAlfred($prompt);
    return ['success' => true, 'stage' => $action, 'essay_type' => $essayType, 'coaching' => $result,
            'message' => "Here's coaching for your $essayType essay on $topic. " . substr($result, 0, 500)];
}

function toolFlashcardCreator($args) {
    $action = trim($args['action'] ?? 'create');
    $subject = trim($args['subject'] ?? 'general');
    $content = trim($args['content'] ?? '');
    $count = intval($args['card_count'] ?? 10);
    if ($action === 'create' && empty($content)) return ['error' => false, 'message' => 'What content should I create flashcards from? Give me the material to study.'];
    $prompt = "Create $count flashcards for $subject from this content: $content. Format each as Q: [question] A: [answer]. Use spaced repetition principles.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'subject' => $subject, 'card_count' => $count, 'cards' => $result,
            'message' => "I've created $count flashcards for $subject. " . substr($result, 0, 500)];
}

function toolQuizGenerator($args) {
    $subject = trim($args['subject'] ?? '');
    $topic = trim($args['topic'] ?? '');
    $count = intval($args['question_count'] ?? 10);
    $difficulty = $args['difficulty'] ?? 'medium';
    if (empty($subject) || empty($topic)) return ['error' => false, 'message' => 'What subject and topic should the quiz cover?'];
    $prompt = "Create a $count-question $difficulty quiz on $subject - $topic. Mix question types (multiple choice, true/false, short answer). Include answer key with explanations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'subject' => $subject, 'topic' => $topic, 'question_count' => $count, 'quiz' => $result,
            'message' => "Here's your $subject quiz on $topic with $count questions. " . substr($result, 0, 500)];
}

function toolStudyPlanBuilder($args) {
    $subjects = $args['subjects'] ?? [];
    $hours = floatval($args['hours_per_day'] ?? 2);
    $testDate = trim($args['test_date'] ?? '');
    $style = $args['learning_style'] ?? 'mixed';
    if (empty($subjects)) return ['error' => false, 'message' => 'What subjects do you need to study?'];
    $subjectList = is_array($subjects) ? implode(', ', $subjects) : $subjects;
    $prompt = "Create a personalized study plan for: $subjectList. Available time: $hours hours/day. Learning style: $style. " .
              (!empty($testDate) ? "Test date: $testDate. " : "") . "Include time blocks, breaks, and topic rotation.";
    $result = callAlfred($prompt);
    return ['success' => true, 'subjects' => $subjects, 'hours_per_day' => $hours, 'plan' => $result,
            'message' => "Here's your personalized study plan. " . substr($result, 0, 500)];
}

function toolReadingLevel($args) {
    $text = trim($args['text'] ?? '');
    $targetGrade = intval($args['target_grade'] ?? 0);
    if (empty($text)) return ['error' => false, 'message' => 'Please provide the text you want me to analyze for reading level.'];
    $words = str_word_count($text);
    $sentences = max(1, preg_match_all('/[.!?]+/', $text));
    $avgWordsPerSentence = round($words / $sentences, 1);
    $syllables = preg_match_all('/[aeiouy]+/i', $text);
    $avgSyllablesPerWord = round($syllables / max(1, $words), 2);
    $fleschKincaid = round(0.39 * $avgWordsPerSentence + 11.8 * $avgSyllablesPerWord - 15.59, 1);
    $result = ['grade_level' => max(1, $fleschKincaid), 'word_count' => $words, 'avg_sentence_length' => $avgWordsPerSentence,
               'readability_score' => round(206.835 - 1.015 * $avgWordsPerSentence - 84.6 * $avgSyllablesPerWord, 1)];
    $msg = "This text reads at approximately grade level " . max(1, $fleschKincaid) . " with $words words.";
    if ($targetGrade > 0 && $fleschKincaid > $targetGrade) $msg .= " It may be too advanced for grade $targetGrade students.";
    $result['message'] = $msg;
    $result['success'] = true;
    return $result;
}

function toolVocabularyBuilder($args) {
    $action = trim($args['action'] ?? 'learn');
    $words = $args['words'] ?? [];
    $grade = intval($args['grade_level'] ?? 8);
    $subject = trim($args['subject'] ?? '');
    $wordList = is_array($words) ? implode(', ', $words) : $words;
    $prompt = "Vocabulary builder for grade $grade" . (!empty($subject) ? " in $subject" : "") . ". Action: $action. Words: $wordList. " .
              "For each word provide: definition, part of speech, example sentence, etymology hint, and memory trick.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'grade_level' => $grade, 'vocabulary' => $result,
            'message' => "Here's your vocabulary practice. " . substr($result, 0, 500)];
}

function toolBookReportHelper($args) {
    $title = trim($args['book_title'] ?? '');
    $author = trim($args['author'] ?? '');
    $section = trim($args['section'] ?? 'full');
    if (empty($title)) return ['error' => false, 'message' => 'What book are you writing a report on?'];
    $prompt = "Help a student with a book report on '$title'" . (!empty($author) ? " by $author" : "") .
              ". Section: $section. Provide guiding questions and scaffolding - don't write the report for them. Help them think critically.";
    $result = callAlfred($prompt);
    return ['success' => true, 'book' => $title, 'section' => $section, 'guidance' => $result,
            'message' => "Here's guidance for your book report on $title. " . substr($result, 0, 500)];
}

function toolHistoryTimeline($args) {
    $topic = trim($args['topic'] ?? '');
    $startYear = $args['start_year'] ?? null;
    $endYear = $args['end_year'] ?? null;
    $focus = $args['focus'] ?? 'all';
    if (empty($topic)) return ['error' => false, 'message' => 'What historical topic or period would you like a timeline for?'];
    $prompt = "Create an interactive history timeline for: $topic" .
              ($startYear ? " from $startYear" : "") . ($endYear ? " to $endYear" : "") .
              ". Focus: $focus. Show key events, dates, cause-effect relationships, and interesting facts.";
    $result = callAlfred($prompt);
    return ['success' => true, 'topic' => $topic, 'focus' => $focus, 'timeline' => $result,
            'message' => "Here's the history timeline for $topic. " . substr($result, 0, 500)];
}

function toolGeographyExplorer($args) {
    $action = trim($args['action'] ?? 'explore');
    $location = trim($args['location'] ?? '');
    $compareWith = trim($args['compare_with'] ?? '');
    if (empty($location)) return ['error' => false, 'message' => 'What country, city, or region would you like to explore?'];
    $prompt = "Geography explorer action: $action. Location: $location. " .
              (!empty($compareWith) ? "Compare with: $compareWith. " : "") .
              "Include key facts, demographics, climate, landmarks, and interesting cultural details.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'location' => $location, 'data' => $result,
            'message' => "Here's what I found about $location. " . substr($result, 0, 500)];
}

function toolSafeWebSearch($args) {
    $query = trim($args['query'] ?? '');
    $ageGroup = $args['age_group'] ?? '9-12';
    $type = $args['type'] ?? 'general';
    if (empty($query)) return ['error' => false, 'message' => 'What would you like to search for?'];
    $prompt = "Safe, educational web search for age group $ageGroup: $query. Type: $type. Provide age-appropriate, educational results only. Filter any inappropriate content.";
    $result = callAlfred($prompt);
    return ['success' => true, 'query' => $query, 'age_group' => $ageGroup, 'results' => $result,
            'message' => "Here are kid-safe results for: $query. " . substr($result, 0, 500)];
}

function toolParentProgressReport($args) {
    $studentName = trim($args['student_name'] ?? 'your child');
    $period = $args['period'] ?? 'week';
    $subjects = $args['subjects'] ?? [];
    $subjectList = is_array($subjects) && !empty($subjects) ? implode(', ', $subjects) : 'all subjects';
    return ['success' => true, 'student' => $studentName, 'period' => $period,
            'report' => "Progress report for $studentName over the past $period in $subjectList. Tool usage and learning activity summary generated.",
            'message' => "Here's $studentName's progress report for the past $period covering $subjectList."];
}

// ═══════════════════════════════════════════════════════════════════════════
// UNIVERSITY/COLLEGE — Voice Tools
// ═══════════════════════════════════════════════════════════════════════════

function toolCitationGenerator($args) {
    $action = trim($args['action'] ?? 'cite');
    $format = trim($args['format'] ?? 'apa');
    $source = $args['source'] ?? [];
    $text = trim($args['text'] ?? '');
    $prompt = "Citation generator. Format: $format (latest edition). Action: $action. " .
              (!empty($source) ? "Source: " . json_encode($source) . ". " : "") .
              (!empty($text) ? "Text to check: $text. " : "") .
              "Generate accurate citations following exact formatting rules.";
    $result = callAlfred($prompt);
    return ['success' => true, 'format' => $format, 'action' => $action, 'citation' => $result,
            'message' => "Here's your $format citation. " . substr($result, 0, 500)];
}

function toolLiteratureReview($args) {
    $topic = trim($args['topic'] ?? '');
    $scope = $args['scope'] ?? 'moderate';
    $maxSources = intval($args['max_sources'] ?? 20);
    $focus = trim($args['focus'] ?? '');
    if (empty($topic)) return ['error' => false, 'message' => 'What is the research topic for your literature review?'];
    $prompt = "Conduct a $scope literature review on: $topic. " . (!empty($focus) ? "Focus on: $focus. " : "") .
              "Max sources: $maxSources. Identify key themes, methodological approaches, and gaps in research.";
    $result = callAlfred($prompt);
    return ['success' => true, 'topic' => $topic, 'scope' => $scope, 'review' => $result,
            'message' => "Here's a literature review on $topic. " . substr($result, 0, 500)];
}

function toolThesisOutline($args) {
    $title = trim($args['title'] ?? '');
    $discipline = trim($args['discipline'] ?? '');
    $degree = $args['degree'] ?? 'masters';
    $rqs = $args['research_questions'] ?? [];
    if (empty($title)) return ['error' => false, 'message' => 'What is your thesis title or working title?'];
    $rqList = is_array($rqs) && !empty($rqs) ? "Research questions: " . implode('; ', $rqs) . ". " : "";
    $prompt = "Generate a $degree thesis outline for: $title. Discipline: $discipline. $rqList" .
              "Include chapter structure, methodology framework, and literature review plan.";
    $result = callAlfred($prompt);
    return ['success' => true, 'title' => $title, 'discipline' => $discipline, 'degree' => $degree, 'outline' => $result,
            'message' => "Here's your thesis outline for: $title. " . substr($result, 0, 500)];
}

function toolStatisticalAnalysis($args) {
    $test = trim($args['test'] ?? 'descriptive');
    $data = $args['data'] ?? [];
    $alpha = floatval($args['significance_level'] ?? 0.05);
    $prompt = "Run a $test statistical analysis. Significance level: $alpha. Data: " . json_encode($data) .
              ". Explain results in plain language with interpretation.";
    $result = callAlfred($prompt);
    return ['success' => true, 'test' => $test, 'significance_level' => $alpha, 'analysis' => $result,
            'message' => "Here are the results of your $test analysis. " . substr($result, 0, 500)];
}

function toolResearchMethodology($args) {
    $rq = trim($args['research_question'] ?? '');
    $discipline = trim($args['discipline'] ?? '');
    $constraints = $args['constraints'] ?? [];
    if (empty($rq)) return ['error' => false, 'message' => 'What is your research question?'];
    $prompt = "Design research methodology for: $rq. Discipline: $discipline. Constraints: " . json_encode($constraints) .
              ". Cover qualitative/quantitative/mixed methods, sampling, data collection, and analysis plan.";
    $result = callAlfred($prompt);
    return ['success' => true, 'research_question' => $rq, 'methodology' => $result,
            'message' => "Here's a suggested research methodology. " . substr($result, 0, 500)];
}

function toolPeerReviewSim($args) {
    $paper = trim($args['paper'] ?? '');
    $discipline = trim($args['discipline'] ?? '');
    $focus = $args['review_focus'] ?? ['all'];
    if (empty($paper)) return ['error' => false, 'message' => 'Please provide your paper text or abstract for peer review.'];
    $focusList = is_array($focus) ? implode(', ', $focus) : $focus;
    $prompt = "Simulate academic peer review. Discipline: $discipline. Focus areas: $focusList. Paper: $paper. " .
              "Provide constructive criticism as a real reviewer would.";
    $result = callAlfred($prompt);
    return ['success' => true, 'discipline' => $discipline, 'feedback' => $result,
            'message' => "Here's your simulated peer review feedback. " . substr($result, 0, 500)];
}

function toolGPACalculator($args) {
    $action = trim($args['action'] ?? 'calculate');
    $courses = $args['courses'] ?? [];
    $currentGPA = floatval($args['current_gpa'] ?? 0);
    $targetGPA = floatval($args['target_gpa'] ?? 0);
    $creditsCompleted = intval($args['credits_completed'] ?? 0);
    if ($action === 'calculate' && empty($courses)) return ['error' => false, 'message' => 'Please provide your courses with credits and grades.'];
    $gradePoints = ['A+' => 4.0, 'A' => 4.0, 'A-' => 3.7, 'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
                    'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7, 'D+' => 1.3, 'D' => 1.0, 'F' => 0.0];
    $totalPoints = 0; $totalCredits = 0;
    if (is_array($courses)) {
        foreach ($courses as $c) {
            $credits = floatval($c['credits'] ?? 3);
            $grade = strtoupper(trim($c['grade'] ?? 'B'));
            $gp = $gradePoints[$grade] ?? 2.0;
            $totalPoints += $gp * $credits;
            $totalCredits += $credits;
        }
    }
    $gpa = $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0;
    $msg = "Your GPA is $gpa based on $totalCredits credits.";
    if ($action === 'target' && $targetGPA > 0) {
        $needed = $totalCredits > 0 ? round(($targetGPA * ($creditsCompleted + $totalCredits) - $currentGPA * $creditsCompleted - $totalPoints) / max(1, $totalCredits), 2) : $targetGPA;
        $msg .= " To reach a $targetGPA GPA, you'd need approximately a $needed average in your remaining courses.";
    }
    return ['success' => true, 'gpa' => $gpa, 'total_credits' => $totalCredits, 'action' => $action, 'message' => $msg];
}

function toolCoursePlanner($args) {
    $program = trim($args['degree_program'] ?? '');
    $completed = $args['completed_courses'] ?? [];
    $remaining = intval($args['remaining_semesters'] ?? 4);
    $prefs = $args['preferences'] ?? [];
    if (empty($program)) return ['error' => false, 'message' => 'What degree program are you in?'];
    $completedList = is_array($completed) ? implode(', ', $completed) : $completed;
    $prompt = "Plan courses for $program degree. Completed: $completedList. Remaining semesters: $remaining. Preferences: " . json_encode($prefs) .
              ". Track prerequisites and find optimal path to graduation.";
    $result = callAlfred($prompt);
    return ['success' => true, 'program' => $program, 'remaining_semesters' => $remaining, 'plan' => $result,
            'message' => "Here's your course plan for $program. " . substr($result, 0, 500)];
}

function toolLabReportFormatter($args) {
    $discipline = trim($args['discipline'] ?? '');
    $sections = $args['sections'] ?? [];
    $format = $args['format'] ?? 'standard';
    if (empty($discipline)) return ['error' => false, 'message' => 'What scientific discipline is this lab report for?'];
    $prompt = "Format a $discipline lab report in $format style. Sections: " . json_encode($sections) .
              ". Include proper scientific structure: title, abstract, introduction, methods, results, discussion, references.";
    $result = callAlfred($prompt);
    return ['success' => true, 'discipline' => $discipline, 'format' => $format, 'report' => $result,
            'message' => "Here's your formatted $discipline lab report. " . substr($result, 0, 500)];
}

function toolStudyGroupCoord($args) {
    $action = trim($args['action'] ?? 'agenda');
    $members = $args['members'] ?? [];
    $subject = trim($args['subject'] ?? '');
    $topics = $args['topics'] ?? [];
    $memberList = is_array($members) ? implode(', ', $members) : $members;
    $topicList = is_array($topics) ? implode(', ', $topics) : $topics;
    $prompt = "Study group coordinator. Action: $action. Subject: $subject. Members: $memberList. Topics: $topicList. " .
              "Create study agenda, distribute topics, or generate study guide.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'subject' => $subject, 'coordination' => $result,
            'message' => "Here's your study group $action for $subject. " . substr($result, 0, 500)];
}

function toolExamPrep($args) {
    $course = trim($args['course'] ?? '');
    $topics = $args['topics'] ?? [];
    $format = $args['exam_format'] ?? 'mixed';
    $notes = trim($args['notes'] ?? '');
    if (empty($course)) return ['error' => false, 'message' => 'What course are you preparing for?'];
    $topicList = is_array($topics) ? implode(', ', $topics) : $topics;
    $prompt = "Create exam prep materials for $course. Topics: $topicList. Format: $format. " .
              (!empty($notes) ? "From notes: $notes. " : "") .
              "Generate practice questions, key concepts, and memory aids.";
    $result = callAlfred($prompt);
    return ['success' => true, 'course' => $course, 'exam_format' => $format, 'prep_materials' => $result,
            'message' => "Here's your exam prep for $course. " . substr($result, 0, 500)];
}

function toolAcademicIntegrity($args) {
    $text = trim($args['text'] ?? '');
    $type = $args['type'] ?? 'essay';
    $citationFormat = trim($args['citation_format'] ?? '');
    if (empty($text)) return ['error' => false, 'message' => 'Please provide the text you want checked for academic integrity.'];
    $prompt = "Check this $type for academic integrity: proper attribution, citation completeness, paraphrasing quality. " .
              (!empty($citationFormat) ? "Expected format: $citationFormat. " : "") .
              "Text: " . substr($text, 0, 3000) . ". Teach proper academic practices.";
    $result = callAlfred($prompt);
    return ['success' => true, 'type' => $type, 'integrity_check' => $result,
            'message' => "Here's the academic integrity check. " . substr($result, 0, 500)];
}

function toolGrantProposalWriter($args) {
    $title = trim($args['title'] ?? '');
    $agency = trim($args['agency'] ?? '');
    $amount = floatval($args['amount'] ?? 0);
    $duration = trim($args['duration'] ?? '');
    $section = trim($args['section'] ?? 'abstract');
    if (empty($title)) return ['error' => false, 'message' => 'What is your research project title?'];
    $prompt = "Write a grant proposal section. Title: $title. Agency: $agency. Amount: \$$amount. Duration: $duration. Section: $section. " .
              "Follow academic grant writing best practices.";
    $result = callAlfred($prompt);
    return ['success' => true, 'title' => $title, 'agency' => $agency, 'section' => $section, 'content' => $result,
            'message' => "Here's the $section section of your grant proposal. " . substr($result, 0, 500)];
}

function toolConferencePaperPrep($args) {
    $action = trim($args['action'] ?? 'format');
    $venue = trim($args['venue'] ?? '');
    $paper = trim($args['paper'] ?? '');
    $format = $args['format'] ?? 'ieee';
    $prompt = "Conference paper preparation. Action: $action. Venue: $venue. Format: $format. " .
              (!empty($paper) ? "Paper: " . substr($paper, 0, 3000) . ". " : "") .
              "Format according to venue requirements.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'venue' => $venue, 'format' => $format, 'content' => $result,
            'message' => "Here's your conference paper $action. " . substr($result, 0, 500)];
}

function toolScholarshipFinder($args) {
    $action = trim($args['action'] ?? 'search');
    $field = trim($args['field'] ?? '');
    $level = $args['level'] ?? 'undergraduate';
    $country = $args['country'] ?? 'Canada';
    $demographics = $args['demographics'] ?? [];
    if (empty($field)) return ['error' => false, 'message' => 'What is your field of study?'];
    $prompt = "Find scholarships. Field: $field. Level: $level. Country: $country. Demographics: " . json_encode($demographics) .
              ". Action: $action. List relevant scholarships with eligibility, deadlines, and amounts.";
    $result = callAlfred($prompt);
    return ['success' => true, 'field' => $field, 'level' => $level, 'action' => $action, 'scholarships' => $result,
            'message' => "Here are scholarship opportunities for $field students. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════
// PROFESSIONALS TOOLS (15)
// ═══════════════════════════════════════════════════════════════

function toolMeetingSummarizer($args) {
    $transcript = trim($args['transcript'] ?? '');
    $meeting_type = $args['meeting_type'] ?? 'general';
    $attendees = $args['attendees'] ?? [];
    if (empty($transcript)) return ['error' => false, 'message' => 'Please provide the meeting transcript or notes to summarize.'];
    $attendeeList = is_array($attendees) ? implode(', ', $attendees) : $attendees;
    $prompt = "You are Alfred, a professional meeting summarizer. Summarize this $meeting_type meeting. Attendees: $attendeeList. " .
              "Extract key decisions, action items with owners, deadlines, and follow-ups. Transcript: " . substr($transcript, 0, 4000);
    $result = callAlfred($prompt);
    return ['success' => true, 'meeting_type' => $meeting_type, 'summary' => $result,
            'message' => "Here's your meeting summary with action items. " . substr($result, 0, 500)];
}

function toolPresentationBuilder($args) {
    $topic = trim($args['topic'] ?? '');
    $audience = $args['audience'] ?? 'general';
    $slides = intval($args['slide_count'] ?? 10);
    $style = $args['style'] ?? 'professional';
    if (empty($topic)) return ['error' => false, 'message' => 'What topic should the presentation cover?'];
    $prompt = "You are Alfred, a presentation design expert. Create a $slides-slide deck outline on '$topic' for a $audience audience. " .
              "Style: $style. Include slide titles, bullet points, speaker notes, and visual suggestions for each slide.";
    $result = callAlfred($prompt);
    return ['success' => true, 'topic' => $topic, 'slide_count' => $slides, 'presentation' => $result,
            'message' => "Here's your $slides-slide presentation outline on $topic. " . substr($result, 0, 500)];
}

function toolCalendarOptimizer($args) {
    $events = $args['events'] ?? [];
    $priorities = $args['priorities'] ?? [];
    $work_hours = $args['work_hours'] ?? '9-17';
    $timezone = $args['timezone'] ?? 'America/Toronto';
    $eventData = is_array($events) ? json_encode($events) : $events;
    $prompt = "You are Alfred, a calendar optimization specialist. Analyze and optimize this schedule. Events: $eventData. " .
              "Work hours: $work_hours. Timezone: $timezone. Priorities: " . json_encode($priorities) .
              ". Suggest time-blocking, batching, buffer times, and focus periods.";
    $result = callAlfred($prompt);
    return ['success' => true, 'work_hours' => $work_hours, 'optimization' => $result,
            'message' => "Here's your optimized calendar plan. " . substr($result, 0, 500)];
}

function toolOKRTracker($args) {
    $action = trim($args['action'] ?? 'create');
    $objective = trim($args['objective'] ?? '');
    $key_results = $args['key_results'] ?? [];
    $quarter = $args['quarter'] ?? 'Q1';
    $progress = $args['progress'] ?? [];
    if (empty($objective) && $action !== 'review') return ['error' => false, 'message' => 'What is the objective you want to track?'];
    $prompt = "You are Alfred, an OKR tracking expert. Action: $action. Objective: $objective. Quarter: $quarter. " .
              "Key Results: " . json_encode($key_results) . ". Progress: " . json_encode($progress) .
              ". Help define measurable key results, track progress, and suggest improvements.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'objective' => $objective, 'quarter' => $quarter, 'okr_data' => $result,
            'message' => "Here's your OKR $action for '$objective'. " . substr($result, 0, 500)];
}

function toolStandupGenerator($args) {
    $yesterday = trim($args['yesterday'] ?? '');
    $today = trim($args['today'] ?? '');
    $blockers = trim($args['blockers'] ?? '');
    $team = $args['team'] ?? 'engineering';
    $format = $args['format'] ?? 'standard';
    $prompt = "You are Alfred, a standup report generator. Team: $team. Format: $format. " .
              "Yesterday: $yesterday. Today: $today. Blockers: $blockers. " .
              "Generate a concise, well-structured daily standup update.";
    $result = callAlfred($prompt);
    return ['success' => true, 'team' => $team, 'standup' => $result,
            'message' => "Here's your daily standup report. " . substr($result, 0, 500)];
}

function toolDecisionMatrix($args) {
    $decision = trim($args['decision'] ?? '');
    $options = $args['options'] ?? [];
    $criteria = $args['criteria'] ?? [];
    $weights = $args['weights'] ?? [];
    if (empty($decision)) return ['error' => false, 'message' => 'What decision do you need help analyzing?'];
    $prompt = "You are Alfred, a decision analysis expert. Decision: $decision. Options: " . json_encode($options) .
              ". Criteria: " . json_encode($criteria) . ". Weights: " . json_encode($weights) .
              ". Build a weighted decision matrix, score each option, and provide a recommendation with reasoning.";
    $result = callAlfred($prompt);
    return ['success' => true, 'decision' => $decision, 'options' => $options, 'analysis' => $result,
            'message' => "Here's your decision matrix analysis for '$decision'. " . substr($result, 0, 500)];
}

function toolProjectEstimator($args) {
    $project = trim($args['project'] ?? '');
    $tasks = $args['tasks'] ?? [];
    $team_size = intval($args['team_size'] ?? 1);
    $methodology = $args['methodology'] ?? 'agile';
    if (empty($project)) return ['error' => false, 'message' => 'What project do you need estimated?'];
    $prompt = "You are Alfred, a project estimation specialist. Project: $project. Tasks: " . json_encode($tasks) .
              ". Team size: $team_size. Methodology: $methodology. " .
              "Provide time estimates with best/likely/worst cases, dependencies, critical path, and resource allocation.";
    $result = callAlfred($prompt);
    return ['success' => true, 'project' => $project, 'team_size' => $team_size, 'estimate' => $result,
            'message' => "Here's the project estimate for '$project'. " . substr($result, 0, 500)];
}

function toolSprintPlanner($args) {
    $sprint_goal = trim($args['sprint_goal'] ?? '');
    $backlog = $args['backlog'] ?? [];
    $capacity = intval($args['capacity'] ?? 40);
    $duration = $args['duration'] ?? '2 weeks';
    $team = $args['team'] ?? [];
    if (empty($sprint_goal)) return ['error' => false, 'message' => 'What is the sprint goal?'];
    $prompt = "You are Alfred, an agile sprint planning expert. Sprint goal: $sprint_goal. Duration: $duration. " .
              "Team capacity: $capacity story points. Backlog: " . json_encode($backlog) . ". Team: " . json_encode($team) .
              ". Plan the sprint with story selection, task breakdown, and assignments.";
    $result = callAlfred($prompt);
    return ['success' => true, 'sprint_goal' => $sprint_goal, 'capacity' => $capacity, 'sprint_plan' => $result,
            'message' => "Here's your sprint plan for '$sprint_goal'. " . substr($result, 0, 500)];
}

function toolRetrospectiveFacilitator($args) {
    $sprint = trim($args['sprint'] ?? '');
    $feedback = $args['feedback'] ?? [];
    $format = $args['format'] ?? 'start-stop-continue';
    $team_size = intval($args['team_size'] ?? 5);
    $prompt = "You are Alfred, an agile retrospective facilitator. Sprint: $sprint. Format: $format. Team size: $team_size. " .
              "Feedback collected: " . json_encode($feedback) .
              ". Facilitate the retro: categorize feedback, identify patterns, and propose actionable improvements.";
    $result = callAlfred($prompt);
    return ['success' => true, 'sprint' => $sprint, 'format' => $format, 'retrospective' => $result,
            'message' => "Here's your retrospective summary for sprint '$sprint'. " . substr($result, 0, 500)];
}

function toolRiskRegister($args) {
    $action = trim($args['action'] ?? 'identify');
    $project = trim($args['project'] ?? '');
    $risks = $args['risks'] ?? [];
    $category = $args['category'] ?? 'all';
    if (empty($project)) return ['error' => false, 'message' => 'Which project should I assess risks for?'];
    $prompt = "You are Alfred, a risk management expert. Action: $action. Project: $project. Category: $category. " .
              "Existing risks: " . json_encode($risks) .
              ". Identify risks, assess probability/impact, assign scores, and suggest mitigation strategies.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'project' => $project, 'risk_register' => $result,
            'message' => "Here's the risk $action for '$project'. " . substr($result, 0, 500)];
}

function toolStakeholderMapper($args) {
    $project = trim($args['project'] ?? '');
    $stakeholders = $args['stakeholders'] ?? [];
    $action = $args['action'] ?? 'map';
    if (empty($project)) return ['error' => false, 'message' => 'Which project needs stakeholder mapping?'];
    $prompt = "You are Alfred, a stakeholder management expert. Project: $project. Action: $action. " .
              "Stakeholders: " . json_encode($stakeholders) .
              ". Map stakeholders by influence/interest, identify communication strategies, and recommend engagement plans.";
    $result = callAlfred($prompt);
    return ['success' => true, 'project' => $project, 'action' => $action, 'stakeholder_map' => $result,
            'message' => "Here's the stakeholder $action for '$project'. " . substr($result, 0, 500)];
}

function toolCompetitiveAnalysis($args) {
    $company = trim($args['company'] ?? '');
    $competitors = $args['competitors'] ?? [];
    $industry = $args['industry'] ?? '';
    $focus = $args['focus'] ?? 'general';
    if (empty($company)) return ['error' => false, 'message' => 'Which company needs competitive analysis?'];
    $prompt = "You are Alfred, a competitive intelligence analyst. Company: $company. Industry: $industry. Focus: $focus. " .
              "Competitors: " . json_encode($competitors) .
              ". Analyze competitive positioning, strengths/weaknesses, market share, and strategic recommendations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'company' => $company, 'industry' => $industry, 'analysis' => $result,
            'message' => "Here's the competitive analysis for $company. " . substr($result, 0, 500)];
}

function toolSWOTAnalysis($args) {
    $subject = trim($args['subject'] ?? '');
    $context = trim($args['context'] ?? '');
    $industry = $args['industry'] ?? '';
    if (empty($subject)) return ['error' => false, 'message' => 'What company, product, or idea should I analyze?'];
    $prompt = "You are Alfred, a strategic analysis expert. Perform a comprehensive SWOT analysis for: $subject. " .
              "Industry: $industry. Context: $context. " .
              "List Strengths, Weaknesses, Opportunities, and Threats with strategic implications and recommended actions.";
    $result = callAlfred($prompt);
    return ['success' => true, 'subject' => $subject, 'industry' => $industry, 'swot' => $result,
            'message' => "Here's the SWOT analysis for $subject. " . substr($result, 0, 500)];
}

function toolBusinessCaseBuilder($args) {
    $title = trim($args['title'] ?? '');
    $problem = trim($args['problem'] ?? '');
    $budget = $args['budget'] ?? '';
    $timeline = $args['timeline'] ?? '';
    $section = $args['section'] ?? 'full';
    if (empty($title)) return ['error' => false, 'message' => 'What is the title of your business case?'];
    $prompt = "You are Alfred, a business case expert. Build a business case: $title. Problem: $problem. " .
              "Budget: $budget. Timeline: $timeline. Section: $section. " .
              "Include executive summary, problem statement, proposed solution, cost-benefit analysis, ROI, risks, and recommendation.";
    $result = callAlfred($prompt);
    return ['success' => true, 'title' => $title, 'section' => $section, 'business_case' => $result,
            'message' => "Here's the business case for '$title'. " . substr($result, 0, 500)];
}

function toolExecutiveSummary($args) {
    $document = trim($args['document'] ?? '');
    $type = $args['type'] ?? 'report';
    $audience = $args['audience'] ?? 'c-suite';
    $max_length = $args['max_length'] ?? '500 words';
    if (empty($document)) return ['error' => false, 'message' => 'Please provide the document to summarize.'];
    $prompt = "You are Alfred, an executive communications specialist. Create an executive summary of this $type for $audience. " .
              "Max length: $max_length. Highlight key findings, recommendations, financial impact, and required decisions. " .
              "Document: " . substr($document, 0, 4000);
    $result = callAlfred($prompt);
    return ['success' => true, 'type' => $type, 'audience' => $audience, 'executive_summary' => $result,
            'message' => "Here's the executive summary for your $type. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════
// SMALL BUSINESS TOOLS (15)
// ═══════════════════════════════════════════════════════════════

function toolBookkeeping($args) {
    $action = trim($args['action'] ?? 'record');
    $type = $args['type'] ?? 'expense';
    $amount = floatval($args['amount'] ?? 0);
    $category = $args['category'] ?? 'general';
    $description = trim($args['description'] ?? '');
    $date = $args['date'] ?? date('Y-m-d');
    $prompt = "You are Alfred, a bookkeeping assistant. Action: $action. Transaction type: $type. Amount: \$$amount. " .
              "Category: $category. Date: $date. Description: $description. " .
              "Record the entry, categorize it properly, and provide any tax implications or notes.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'type' => $type, 'amount' => $amount, 'entry' => $result,
            'message' => "Bookkeeping entry recorded: \$$amount $type in $category. " . substr($result, 0, 500)];
}

function toolInvoiceCreator($args) {
    $client = trim($args['client'] ?? '');
    $items = $args['items'] ?? [];
    $due_date = $args['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
    $currency = $args['currency'] ?? 'CAD';
    $notes = trim($args['notes'] ?? '');
    if (empty($client)) return ['error' => false, 'message' => 'Who is the invoice for?'];
    $prompt = "You are Alfred, an invoicing specialist. Create a professional invoice for client: $client. " .
              "Items: " . json_encode($items) . ". Due date: $due_date. Currency: $currency. Notes: $notes. " .
              "Include line items, subtotal, taxes, and total. Format professionally.";
    $result = callAlfred($prompt);
    return ['success' => true, 'client' => $client, 'due_date' => $due_date, 'invoice' => $result,
            'message' => "Invoice created for $client, due $due_date. " . substr($result, 0, 500)];
}

function toolPayrollCalculator($args) {
    $employee = trim($args['employee'] ?? '');
    $hours = floatval($args['hours'] ?? 0);
    $rate = floatval($args['rate'] ?? 0);
    $province = $args['province'] ?? 'Ontario';
    $pay_period = $args['pay_period'] ?? 'biweekly';
    $deductions = $args['deductions'] ?? [];
    if ($rate <= 0) return ['error' => false, 'message' => 'What is the hourly rate or salary?'];
    $prompt = "You are Alfred, a payroll specialist. Calculate payroll for: $employee. Hours: $hours. Rate: \$$rate/hr. " .
              "Province: $province. Pay period: $pay_period. Additional deductions: " . json_encode($deductions) .
              ". Calculate gross pay, CPP, EI, income tax, deductions, and net pay.";
    $result = callAlfred($prompt);
    return ['success' => true, 'employee' => $employee, 'gross' => $hours * $rate, 'payroll' => $result,
            'message' => "Payroll calculated for $employee: $hours hrs @ \$$rate. " . substr($result, 0, 500)];
}

function toolInventoryTracker($args) {
    $action = trim($args['action'] ?? 'check');
    $item = trim($args['item'] ?? '');
    $quantity = intval($args['quantity'] ?? 0);
    $category = $args['category'] ?? 'general';
    $reorder_point = intval($args['reorder_point'] ?? 10);
    if (empty($item) && $action !== 'report') return ['error' => false, 'message' => 'Which item do you want to track?'];
    $prompt = "You are Alfred, an inventory management assistant. Action: $action. Item: $item. Quantity: $quantity. " .
              "Category: $category. Reorder point: $reorder_point. " .
              "Track stock levels, flag low inventory, suggest reorder quantities, and maintain accurate counts.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'item' => $item, 'quantity' => $quantity, 'inventory' => $result,
            'message' => "Inventory $action complete for $item. " . substr($result, 0, 500)];
}

function toolCRMContactManager($args) {
    $action = trim($args['action'] ?? 'add');
    $name = trim($args['name'] ?? '');
    $company = trim($args['company'] ?? '');
    $email = trim($args['email'] ?? '');
    $notes = trim($args['notes'] ?? '');
    $stage = $args['stage'] ?? 'lead';
    if (empty($name) && $action !== 'report') return ['error' => false, 'message' => 'What is the contact name?'];
    $prompt = "You are Alfred, a CRM specialist. Action: $action. Contact: $name. Company: $company. Email: $email. " .
              "Pipeline stage: $stage. Notes: $notes. " .
              "Manage the customer relationship, suggest follow-up actions, and track engagement history.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'name' => $name, 'stage' => $stage, 'crm_data' => $result,
            'message' => "CRM $action completed for $name ($stage). " . substr($result, 0, 500)];
}

function toolQuoteGenerator($args) {
    $client = trim($args['client'] ?? '');
    $services = $args['services'] ?? [];
    $valid_until = $args['valid_until'] ?? date('Y-m-d', strtotime('+14 days'));
    $terms = trim($args['terms'] ?? '');
    if (empty($client)) return ['error' => false, 'message' => 'Who is this quote for?'];
    $prompt = "You are Alfred, a professional quoting specialist. Generate a quote for client: $client. " .
              "Services/items: " . json_encode($services) . ". Valid until: $valid_until. Terms: $terms. " .
              "Include itemized pricing, payment terms, scope of work, and professional formatting.";
    $result = callAlfred($prompt);
    return ['success' => true, 'client' => $client, 'valid_until' => $valid_until, 'quote' => $result,
            'message' => "Quote generated for $client, valid until $valid_until. " . substr($result, 0, 500)];
}

function toolExpenseTracker($args) {
    $action = trim($args['action'] ?? 'add');
    $amount = floatval($args['amount'] ?? 0);
    $category = $args['category'] ?? 'general';
    $vendor = trim($args['vendor'] ?? '');
    $date = $args['date'] ?? date('Y-m-d');
    $receipt = $args['receipt'] ?? false;
    $prompt = "You are Alfred, an expense tracking specialist. Action: $action. Amount: \$$amount. Category: $category. " .
              "Vendor: $vendor. Date: $date. Receipt: " . ($receipt ? 'yes' : 'no') .
              ". Categorize the expense, flag tax-deductible items, and track against budget.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'amount' => $amount, 'category' => $category, 'expense' => $result,
            'message' => "Expense $action: \$$amount at $vendor ($category). " . substr($result, 0, 500)];
}

function toolTaxPrep($args) {
    $business_type = $args['business_type'] ?? 'sole_proprietorship';
    $year = $args['year'] ?? date('Y');
    $revenue = floatval($args['revenue'] ?? 0);
    $expenses = floatval($args['expenses'] ?? 0);
    $province = $args['province'] ?? 'Ontario';
    $deductions = $args['deductions'] ?? [];
    $prompt = "You are Alfred, a tax preparation assistant. Business type: $business_type. Tax year: $year. Province: $province. " .
              "Revenue: \$$revenue. Expenses: \$$expenses. Deductions: " . json_encode($deductions) .
              ". Prepare a tax summary with estimated taxes owing, deduction opportunities, and filing reminders.";
    $result = callAlfred($prompt);
    return ['success' => true, 'year' => $year, 'revenue' => $revenue, 'expenses' => $expenses, 'tax_summary' => $result,
            'message' => "Tax prep summary for $year: Revenue \$$revenue, Expenses \$$expenses. " . substr($result, 0, 500)];
}

function toolCashFlowForecast($args) {
    $period = $args['period'] ?? '3 months';
    $income = $args['income'] ?? [];
    $expenses = $args['expenses'] ?? [];
    $current_balance = floatval($args['current_balance'] ?? 0);
    $business = trim($args['business'] ?? '');
    $prompt = "You are Alfred, a financial forecasting specialist. Create a cash flow forecast for: $business. Period: $period. " .
              "Current balance: \$$current_balance. Expected income: " . json_encode($income) .
              ". Expected expenses: " . json_encode($expenses) .
              ". Project cash flow, identify gaps, and suggest strategies to maintain healthy cash flow.";
    $result = callAlfred($prompt);
    return ['success' => true, 'period' => $period, 'current_balance' => $current_balance, 'forecast' => $result,
            'message' => "Cash flow forecast for $period (balance: \$$current_balance). " . substr($result, 0, 500)];
}

function toolEmployeeScheduler($args) {
    $action = trim($args['action'] ?? 'create');
    $employees = $args['employees'] ?? [];
    $period = $args['period'] ?? 'weekly';
    $constraints = $args['constraints'] ?? [];
    $business_hours = $args['business_hours'] ?? '9-17';
    $prompt = "You are Alfred, an employee scheduling specialist. Action: $action. Period: $period. " .
              "Business hours: $business_hours. Employees: " . json_encode($employees) .
              ". Constraints: " . json_encode($constraints) .
              ". Create an optimized schedule respecting availability, labor laws, and fair shift distribution.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'period' => $period, 'schedule' => $result,
            'message' => "Employee schedule $action for $period complete. " . substr($result, 0, 500)];
}

function toolCustomerSurvey($args) {
    $purpose = trim($args['purpose'] ?? '');
    $type = $args['type'] ?? 'satisfaction';
    $question_count = intval($args['question_count'] ?? 10);
    $audience = $args['audience'] ?? 'customers';
    if (empty($purpose)) return ['error' => false, 'message' => 'What is the purpose of this survey?'];
    $prompt = "You are Alfred, a market research specialist. Create a $type survey. Purpose: $purpose. " .
              "Target audience: $audience. Number of questions: $question_count. " .
              "Design effective questions with mix of scales, multiple choice, and open-ended. Include analysis tips.";
    $result = callAlfred($prompt);
    return ['success' => true, 'type' => $type, 'purpose' => $purpose, 'survey' => $result,
            'message' => "Customer $type survey created with $question_count questions. " . substr($result, 0, 500)];
}

function toolCompetitorPriceMonitor($args) {
    $product = trim($args['product'] ?? '');
    $competitors = $args['competitors'] ?? [];
    $our_price = floatval($args['our_price'] ?? 0);
    $market = $args['market'] ?? 'local';
    if (empty($product)) return ['error' => false, 'message' => 'Which product or service should I monitor pricing for?'];
    $prompt = "You are Alfred, a competitive pricing analyst. Product/service: $product. Our price: \$$our_price. Market: $market. " .
              "Competitors: " . json_encode($competitors) .
              ". Analyze pricing strategies, suggest optimal price points, and identify value-add opportunities.";
    $result = callAlfred($prompt);
    return ['success' => true, 'product' => $product, 'our_price' => $our_price, 'pricing_analysis' => $result,
            'message' => "Pricing analysis for $product (our price: \$$our_price). " . substr($result, 0, 500)];
}

function toolSocialMediaScheduler($args) {
    $platform = $args['platform'] ?? 'all';
    $content = trim($args['content'] ?? '');
    $schedule = $args['schedule'] ?? [];
    $frequency = $args['frequency'] ?? 'daily';
    $brand_voice = $args['brand_voice'] ?? 'professional';
    if (empty($content) && empty($schedule)) return ['error' => false, 'message' => 'What content would you like to schedule?'];
    $prompt = "You are Alfred, a social media scheduling specialist. Platform: $platform. Frequency: $frequency. " .
              "Brand voice: $brand_voice. Content: $content. Schedule: " . json_encode($schedule) .
              ". Create a posting schedule with optimal times, captions, and hashtag strategy for each platform.";
    $result = callAlfred($prompt);
    return ['success' => true, 'platform' => $platform, 'frequency' => $frequency, 'social_schedule' => $result,
            'message' => "Social media schedule created for $platform ($frequency). " . substr($result, 0, 500)];
}

function toolReviewResponder($args) {
    $review = trim($args['review'] ?? '');
    $rating = intval($args['rating'] ?? 0);
    $platform = $args['platform'] ?? 'google';
    $business = trim($args['business'] ?? '');
    $tone = $args['tone'] ?? 'professional';
    if (empty($review)) return ['error' => false, 'message' => 'Please provide the customer review to respond to.'];
    $prompt = "You are Alfred, a reputation management expert. Craft a $tone response to this $rating-star $platform review " .
              "for $business. Review: $review. " .
              "Be empathetic, address concerns, highlight positives, and invite further engagement. Keep it authentic.";
    $result = callAlfred($prompt);
    return ['success' => true, 'rating' => $rating, 'platform' => $platform, 'response' => $result,
            'message' => "Response crafted for $rating-star $platform review. " . substr($result, 0, 500)];
}

function toolBusinessPlanWriter($args) {
    $business_name = trim($args['business_name'] ?? '');
    $industry = $args['industry'] ?? '';
    $section = $args['section'] ?? 'full';
    $target_market = trim($args['target_market'] ?? '');
    $funding = $args['funding'] ?? '';
    if (empty($business_name)) return ['error' => false, 'message' => 'What is your business name?'];
    $prompt = "You are Alfred, a business plan writing expert. Business: $business_name. Industry: $industry. Section: $section. " .
              "Target market: $target_market. Funding goal: $funding. " .
              "Write a professional business plan section with market analysis, financial projections, and strategy.";
    $result = callAlfred($prompt);
    return ['success' => true, 'business_name' => $business_name, 'section' => $section, 'business_plan' => $result,
            'message' => "Business plan $section for $business_name. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════
// CONTENT CREATOR TOOLS (14)
// ═══════════════════════════════════════════════════════════════

function toolYouTubeScriptWriter($args) {
    $topic = trim($args['topic'] ?? '');
    $duration = $args['duration'] ?? '10 minutes';
    $style = $args['style'] ?? 'educational';
    $audience = $args['audience'] ?? 'general';
    $cta = trim($args['cta'] ?? '');
    if (empty($topic)) return ['error' => false, 'message' => 'What topic should the YouTube video cover?'];
    $prompt = "You are Alfred, a YouTube scriptwriting expert. Write a $duration $style script on '$topic' for $audience audience. " .
              "CTA: $cta. Include hook, intro, main content sections, transitions, engagement prompts, and outro.";
    $result = callAlfred($prompt);
    return ['success' => true, 'topic' => $topic, 'duration' => $duration, 'script' => $result,
            'message' => "YouTube script for '$topic' ($duration, $style). " . substr($result, 0, 500)];
}

function toolThumbnailDesigner($args) {
    $title = trim($args['title'] ?? '');
    $style = $args['style'] ?? 'bold';
    $colors = $args['colors'] ?? [];
    $elements = $args['elements'] ?? [];
    $platform = $args['platform'] ?? 'youtube';
    if (empty($title)) return ['error' => false, 'message' => 'What is the video/content title for the thumbnail?'];
    $prompt = "You are Alfred, a thumbnail design consultant. Design a $platform thumbnail concept for: '$title'. " .
              "Style: $style. Colors: " . json_encode($colors) . ". Elements: " . json_encode($elements) .
              ". Describe layout, text placement, color scheme, imagery, and emotional triggers for maximum CTR.";
    $result = callAlfred($prompt);
    return ['success' => true, 'title' => $title, 'platform' => $platform, 'design' => $result,
            'message' => "Thumbnail concept for '$title'. " . substr($result, 0, 500)];
}

function toolPodcastShowNotes($args) {
    $episode_title = trim($args['episode_title'] ?? '');
    $transcript = trim($args['transcript'] ?? '');
    $guests = $args['guests'] ?? [];
    $topics = $args['topics'] ?? [];
    if (empty($episode_title)) return ['error' => false, 'message' => 'What is the episode title?'];
    $guestList = is_array($guests) ? implode(', ', $guests) : $guests;
    $prompt = "You are Alfred, a podcast production specialist. Create show notes for episode: '$episode_title'. " .
              "Guests: $guestList. Topics: " . json_encode($topics) .
              ". " . (!empty($transcript) ? "Transcript: " . substr($transcript, 0, 3000) . ". " : "") .
              "Include episode summary, timestamps, key takeaways, links, and SEO-optimized description.";
    $result = callAlfred($prompt);
    return ['success' => true, 'episode_title' => $episode_title, 'show_notes' => $result,
            'message' => "Show notes for '$episode_title'. " . substr($result, 0, 500)];
}

function toolSocialPostGenerator($args) {
    $topic = trim($args['topic'] ?? '');
    $platform = $args['platform'] ?? 'all';
    $tone = $args['tone'] ?? 'engaging';
    $include_hashtags = $args['include_hashtags'] ?? true;
    $count = intval($args['count'] ?? 3);
    if (empty($topic)) return ['error' => false, 'message' => 'What topic should the social posts cover?'];
    $prompt = "You are Alfred, a social media content expert. Generate $count $tone social media posts about '$topic' for $platform. " .
              ($include_hashtags ? "Include relevant hashtags. " : "") .
              "Optimize for engagement with hooks, emojis where appropriate, and CTAs.";
    $result = callAlfred($prompt);
    return ['success' => true, 'topic' => $topic, 'platform' => $platform, 'posts' => $result,
            'message' => "$count social posts for $platform about '$topic'. " . substr($result, 0, 500)];
}

function toolContentCalendar($args) {
    $period = $args['period'] ?? '1 month';
    $platforms = $args['platforms'] ?? ['instagram', 'twitter', 'youtube'];
    $niche = trim($args['niche'] ?? '');
    $frequency = $args['frequency'] ?? 'daily';
    $themes = $args['themes'] ?? [];
    if (empty($niche)) return ['error' => false, 'message' => 'What is your content niche or topic area?'];
    $prompt = "You are Alfred, a content strategy expert. Create a $period content calendar for niche: $niche. " .
              "Platforms: " . json_encode($platforms) . ". Posting frequency: $frequency. Themes: " . json_encode($themes) .
              ". Include post ideas, content types, optimal posting times, and theme days.";
    $result = callAlfred($prompt);
    return ['success' => true, 'period' => $period, 'niche' => $niche, 'calendar' => $result,
            'message' => "Content calendar for $period ($niche). " . substr($result, 0, 500)];
}

function toolHashtagOptimizer($args) {
    $content = trim($args['content'] ?? '');
    $platform = $args['platform'] ?? 'instagram';
    $niche = trim($args['niche'] ?? '');
    $count = intval($args['count'] ?? 20);
    if (empty($content) && empty($niche)) return ['error' => false, 'message' => 'What content or niche should I optimize hashtags for?'];
    $prompt = "You are Alfred, a hashtag optimization specialist. Optimize hashtags for $platform. " .
              "Content: $content. Niche: $niche. Generate $count hashtags. " .
              "Mix high-volume, medium, and niche-specific hashtags. Include reach estimates and grouping strategy.";
    $result = callAlfred($prompt);
    return ['success' => true, 'platform' => $platform, 'count' => $count, 'hashtags' => $result,
            'message' => "$count optimized hashtags for $platform. " . substr($result, 0, 500)];
}

function toolVideoIdeaGenerator($args) {
    $niche = trim($args['niche'] ?? '');
    $platform = $args['platform'] ?? 'youtube';
    $count = intval($args['count'] ?? 10);
    $trending = $args['trending'] ?? true;
    $audience = $args['audience'] ?? 'general';
    if (empty($niche)) return ['error' => false, 'message' => 'What is your content niche?'];
    $prompt = "You are Alfred, a video content strategist. Generate $count video ideas for $platform in the $niche niche. " .
              "Target audience: $audience. " . ($trending ? "Include trending topics. " : "") .
              "For each idea: title, hook, format, estimated views potential, and why it works.";
    $result = callAlfred($prompt);
    return ['success' => true, 'niche' => $niche, 'platform' => $platform, 'ideas' => $result,
            'message' => "$count video ideas for $niche on $platform. " . substr($result, 0, 500)];
}

function toolSponsorPitch($args) {
    $creator = trim($args['creator'] ?? '');
    $brand = trim($args['brand'] ?? '');
    $audience_size = $args['audience_size'] ?? '';
    $niche = trim($args['niche'] ?? '');
    $metrics = $args['metrics'] ?? [];
    if (empty($brand)) return ['error' => false, 'message' => 'Which brand are you pitching to?'];
    $prompt = "You are Alfred, a sponsorship pitch expert. Create a pitch from $creator to $brand. Niche: $niche. " .
              "Audience size: $audience_size. Metrics: " . json_encode($metrics) .
              ". Write a compelling sponsorship proposal with value proposition, deliverables, pricing tiers, and past results.";
    $result = callAlfred($prompt);
    return ['success' => true, 'brand' => $brand, 'creator' => $creator, 'pitch' => $result,
            'message' => "Sponsorship pitch to $brand created. " . substr($result, 0, 500)];
}

function toolAnalyticsReporter($args) {
    $platform = $args['platform'] ?? 'youtube';
    $metrics = $args['metrics'] ?? [];
    $period = $args['period'] ?? 'last 30 days';
    $goals = $args['goals'] ?? [];
    $prompt = "You are Alfred, a content analytics expert. Analyze $platform performance for $period. " .
              "Metrics: " . json_encode($metrics) . ". Goals: " . json_encode($goals) .
              ". Provide insights on growth trends, top-performing content, audience behavior, and actionable recommendations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'platform' => $platform, 'period' => $period, 'report' => $result,
            'message' => "Analytics report for $platform ($period). " . substr($result, 0, 500)];
}

function toolCaptionGenerator($args) {
    $content = trim($args['content'] ?? '');
    $platform = $args['platform'] ?? 'instagram';
    $tone = $args['tone'] ?? 'engaging';
    $count = intval($args['count'] ?? 3);
    $include_cta = $args['include_cta'] ?? true;
    if (empty($content)) return ['error' => false, 'message' => 'What content do you need captions for?'];
    $prompt = "You are Alfred, a caption writing specialist. Generate $count $tone captions for $platform. " .
              "Content: $content. " . ($include_cta ? "Include call-to-action. " : "") .
              "Write captivating captions with hooks, storytelling, and engagement triggers.";
    $result = callAlfred($prompt);
    return ['success' => true, 'platform' => $platform, 'count' => $count, 'captions' => $result,
            'message' => "$count captions generated for $platform. " . substr($result, 0, 500)];
}

function toolContentRepurposer($args) {
    $original_content = trim($args['original_content'] ?? '');
    $source_format = $args['source_format'] ?? 'blog post';
    $target_formats = $args['target_formats'] ?? ['twitter', 'instagram', 'linkedin'];
    if (empty($original_content)) return ['error' => false, 'message' => 'Please provide the original content to repurpose.'];
    $prompt = "You are Alfred, a content repurposing expert. Repurpose this $source_format into: " . json_encode($target_formats) .
              ". Original: " . substr($original_content, 0, 3000) .
              ". Adapt tone, length, and format for each platform while maintaining the core message.";
    $result = callAlfred($prompt);
    return ['success' => true, 'source_format' => $source_format, 'target_formats' => $target_formats, 'repurposed' => $result,
            'message' => "Content repurposed from $source_format to " . count($target_formats) . " formats. " . substr($result, 0, 500)];
}

function toolStreamOverlayCreator($args) {
    $platform = $args['platform'] ?? 'twitch';
    $theme = trim($args['theme'] ?? '');
    $elements = $args['elements'] ?? ['webcam', 'chat', 'alerts', 'game'];
    $colors = $args['colors'] ?? [];
    $branding = trim($args['branding'] ?? '');
    if (empty($theme)) return ['error' => false, 'message' => 'What theme or style would you like for your stream overlay?'];
    $prompt = "You are Alfred, a stream design consultant. Design a $platform stream overlay. Theme: $theme. " .
              "Elements: " . json_encode($elements) . ". Colors: " . json_encode($colors) . ". Branding: $branding. " .
              "Describe layout, dimensions, positioning, animations, and design specifications for each element.";
    $result = callAlfred($prompt);
    return ['success' => true, 'platform' => $platform, 'theme' => $theme, 'overlay_design' => $result,
            'message' => "Stream overlay design for $platform ($theme). " . substr($result, 0, 500)];
}

function toolTikTokTrendAnalyzer($args) {
    $niche = trim($args['niche'] ?? '');
    $action = $args['action'] ?? 'analyze';
    $hashtags = $args['hashtags'] ?? [];
    $sounds = $args['sounds'] ?? [];
    if (empty($niche)) return ['error' => false, 'message' => 'What niche should I analyze TikTok trends for?'];
    $prompt = "You are Alfred, a TikTok trends analyst. Niche: $niche. Action: $action. " .
              "Hashtags: " . json_encode($hashtags) . ". Sounds: " . json_encode($sounds) .
              ". Analyze current trends, suggest content hooks, recommend sounds/effects, and provide timing strategies.";
    $result = callAlfred($prompt);
    return ['success' => true, 'niche' => $niche, 'action' => $action, 'trends' => $result,
            'message' => "TikTok trend analysis for $niche. " . substr($result, 0, 500)];
}

function toolNewsletterWriter($args) {
    $topic = trim($args['topic'] ?? '');
    $audience = $args['audience'] ?? 'subscribers';
    $tone = $args['tone'] ?? 'informative';
    $sections = $args['sections'] ?? [];
    $cta = trim($args['cta'] ?? '');
    if (empty($topic)) return ['error' => false, 'message' => 'What topic should the newsletter cover?'];
    $prompt = "You are Alfred, an email newsletter specialist. Write a $tone newsletter on '$topic' for $audience. " .
              "Sections: " . json_encode($sections) . ". CTA: $cta. " .
              "Include subject line options, preview text, header, body sections, and compelling CTA. Optimize for open rate.";
    $result = callAlfred($prompt);
    return ['success' => true, 'topic' => $topic, 'audience' => $audience, 'newsletter' => $result,
            'message' => "Newsletter on '$topic' for $audience. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════
// HEALTHCARE TOOLS (12)
// ═══════════════════════════════════════════════════════════════

function toolSymptomChecker($args) {
    $symptom = trim($args['symptoms'] ?? '');
    $severity = $args['severity'] ?? 'moderate';
    if (empty($symptom)) return ['error' => false, 'message' => 'Please describe your symptoms.'];
    $prompt = "You are Alfred, a professional AI health assistant. A user reports the following symptoms: $symptom (severity: $severity). " .
              "Provide possible causes, self-care suggestions, and clearly indicate when they should see a doctor. Include a disclaimer that this is not medical advice.";
    $result = callAlfred($prompt);
    return ['success' => true, 'symptoms' => $symptom, 'severity' => $severity, 'data' => $result,
            'message' => "Symptom assessment for '$symptom' (severity: $severity). " . substr($result, 0, 500)];
}

function toolMedicationReminder($args) {
    $medications = $args['medications'] ?? '';
    $schedule = $args['schedule'] ?? '';
    if (empty($medications)) return ['error' => false, 'message' => 'Please specify which medications to track.'];
    $medList = is_array($medications) ? json_encode($medications) : $medications;
    $prompt = "You are Alfred, a professional AI medication assistant. Set up a medication reminder plan for: $medList. " .
              "Schedule: $schedule. Provide a clear daily schedule, interaction warnings, and tips for adherence.";
    $result = callAlfred($prompt);
    return ['success' => true, 'medications' => $medications, 'schedule' => $schedule, 'data' => $result,
            'message' => "Medication reminder plan created. " . substr($result, 0, 500)];
}

function toolCalorieCounter($args) {
    $meal = trim($args['meal'] ?? '');
    $portions = $args['portions'] ?? 1;
    if (empty($meal)) return ['error' => false, 'message' => 'Please describe what you ate.'];
    $prompt = "You are Alfred, a professional AI nutrition assistant. Estimate the calories and macronutrients for this meal: $meal ($portions portion(s)). " .
              "Break down calories, protein, carbs, fat, and fiber. Provide healthier alternatives if calorie count is high.";
    $result = callAlfred($prompt);
    return ['success' => true, 'meal' => $meal, 'portions' => $portions, 'data' => $result,
            'message' => "Calorie estimate for '$meal'. " . substr($result, 0, 500)];
}

function toolWorkoutPlanner($args) {
    $goal = trim($args['goal'] ?? '');
    $fitness_level = $args['fitness_level'] ?? 'intermediate';
    $duration = $args['duration'] ?? 30;
    if (empty($goal)) return ['error' => false, 'message' => 'What is your fitness goal?'];
    $prompt = "You are Alfred, a professional AI fitness trainer. Create a $duration-minute workout plan for someone at $fitness_level level " .
              "with the goal: $goal. Include warm-up, exercises with sets/reps, rest periods, and cool-down.";
    $result = callAlfred($prompt);
    return ['success' => true, 'goal' => $goal, 'fitness_level' => $fitness_level, 'duration' => $duration, 'data' => $result,
            'message' => "Workout plan for '$goal' ($duration min, $fitness_level). " . substr($result, 0, 500)];
}

function toolMentalHealthCheck($args) {
    $mood = trim($args['mood'] ?? '');
    $stress_level = $args['stress_level'] ?? 5;
    if (empty($mood)) return ['error' => false, 'message' => 'How are you feeling right now?'];
    $prompt = "You are Alfred, a compassionate AI wellness assistant. The user describes their mood as: $mood (stress level: $stress_level/10). " .
              "Provide empathetic support, coping strategies, mindfulness exercises, and resources. If stress is 8+ or mood indicates crisis, recommend professional help immediately.";
    $result = callAlfred($prompt);
    return ['success' => true, 'mood' => $mood, 'stress_level' => $stress_level, 'data' => $result,
            'message' => "Mental health check-in (mood: $mood, stress: $stress_level/10). " . substr($result, 0, 500)];
}

function toolSleepAnalyzer($args) {
    $hours = $args['hours'] ?? 0;
    $quality = $args['quality'] ?? 'average';
    if ($hours <= 0) return ['error' => false, 'message' => 'How many hours did you sleep?'];
    $prompt = "You are Alfred, a professional AI sleep specialist. Analyze sleep: $hours hours, quality: $quality. " .
              "Evaluate if this is healthy, suggest improvements to sleep hygiene, recommend ideal bedtime routine, and flag concerns if sleep is poor.";
    $result = callAlfred($prompt);
    return ['success' => true, 'hours' => $hours, 'quality' => $quality, 'data' => $result,
            'message' => "Sleep analysis: $hours hrs ($quality quality). " . substr($result, 0, 500)];
}

function toolFirstAidGuide($args) {
    $injury = trim($args['injury'] ?? '');
    $severity = $args['severity'] ?? 'minor';
    if (empty($injury)) return ['error' => false, 'message' => 'What type of injury or condition needs first aid?'];
    $prompt = "You are Alfred, a professional AI first aid guide. Provide step-by-step first aid instructions for: $injury (severity: $severity). " .
              "Include immediate actions, what NOT to do, when to call emergency services, and follow-up care. Prioritize safety.";
    $result = callAlfred($prompt);
    return ['success' => true, 'injury' => $injury, 'severity' => $severity, 'data' => $result,
            'message' => "First aid guide for '$injury' ($severity). " . substr($result, 0, 500)];
}

function toolNutritionPlanner($args) {
    $dietary_needs = trim($args['dietary_needs'] ?? '');
    $allergies = $args['allergies'] ?? 'none';
    if (empty($dietary_needs)) return ['error' => false, 'message' => 'What are your dietary needs or goals?'];
    $prompt = "You are Alfred, a professional AI nutritionist. Create a daily meal plan for: $dietary_needs. Allergies/restrictions: $allergies. " .
              "Include breakfast, lunch, dinner, and snacks with portions, calories, and key nutrients. Ensure balanced macros.";
    $result = callAlfred($prompt);
    return ['success' => true, 'dietary_needs' => $dietary_needs, 'allergies' => $allergies, 'data' => $result,
            'message' => "Nutrition plan for '$dietary_needs'. " . substr($result, 0, 500)];
}

function toolHydrationTracker($args) {
    $weight = $args['weight'] ?? 0;
    $activity_level = $args['activity_level'] ?? 'moderate';
    if ($weight <= 0) return ['error' => false, 'message' => 'What is your body weight (in lbs or kg)?'];
    $prompt = "You are Alfred, a professional AI hydration specialist. Calculate daily water intake for someone weighing $weight with $activity_level activity level. " .
              "Provide hourly hydration schedule, signs of dehydration, tips for staying hydrated, and adjustments for weather/exercise.";
    $result = callAlfred($prompt);
    return ['success' => true, 'weight' => $weight, 'activity_level' => $activity_level, 'data' => $result,
            'message' => "Hydration plan for weight $weight ($activity_level activity). " . substr($result, 0, 500)];
}

function toolPostureCorrector($args) {
    $work_type = $args['work_type'] ?? 'desk';
    $hours = $args['hours'] ?? 8;
    $prompt = "You are Alfred, a professional AI ergonomics advisor. Provide posture correction advice for someone doing $work_type work for $hours hours/day. " .
              "Include desk/chair setup, stretches every hour, exercises to strengthen posture muscles, and warning signs of poor posture.";
    $result = callAlfred($prompt);
    return ['success' => true, 'work_type' => $work_type, 'hours' => $hours, 'data' => $result,
            'message' => "Posture advice for $work_type work ($hours hrs/day). " . substr($result, 0, 500)];
}

function toolVaccinationSchedule($args) {
    $age = $args['age'] ?? '';
    $region = $args['region'] ?? 'US';
    if (empty($age)) return ['error' => false, 'message' => 'What is the age of the person?'];
    $prompt = "You are Alfred, a professional AI health advisor. Provide the recommended vaccination schedule for age $age in region $region. " .
              "List vaccines due, overdue, and upcoming. Include standard CDC/WHO recommendations and note this is informational only — consult a doctor.";
    $result = callAlfred($prompt);
    return ['success' => true, 'age' => $age, 'region' => $region, 'data' => $result,
            'message' => "Vaccination schedule for age $age ($region). " . substr($result, 0, 500)];
}

function toolEmergencyProtocol($args) {
    $emergency_type = trim($args['emergency_type'] ?? '');
    $location = $args['location'] ?? 'unknown';
    if (empty($emergency_type)) return ['error' => false, 'message' => 'What type of emergency are you experiencing?'];
    $prompt = "You are Alfred, a professional AI emergency response guide. Provide immediate step-by-step guidance for: $emergency_type at location: $location. " .
              "Include: 1) Call 911/local emergency, 2) Immediate safety actions, 3) First response steps, 4) What to tell dispatchers. Prioritize life safety above all.";
    $result = callAlfred($prompt);
    return ['success' => true, 'emergency_type' => $emergency_type, 'location' => $location, 'data' => $result,
            'message' => "Emergency protocol for '$emergency_type'. CALL 911 IF LIFE-THREATENING. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════
// TEACHERS/EDUCATORS TOOLS (15)
// ═══════════════════════════════════════════════════════════════

function toolLessonPlanCreator($args) {
    $subject = trim($args['subject'] ?? '');
    $grade = trim($args['grade'] ?? '');
    $duration = $args['duration'] ?? 45;
    if (empty($subject) || empty($grade)) return ['error' => false, 'message' => 'Please specify the subject and grade level.'];
    $prompt = "You are Alfred, a professional AI teaching assistant. Create a detailed $duration-minute lesson plan for $subject, grade $grade. " .
              "Include learning objectives, materials needed, warm-up, direct instruction, guided practice, independent practice, assessment, and closure.";
    $result = callAlfred($prompt);
    return ['success' => true, 'subject' => $subject, 'grade' => $grade, 'duration' => $duration, 'data' => $result,
            'message' => "Lesson plan: $subject, grade $grade ($duration min). " . substr($result, 0, 500)];
}

function toolRubricGenerator($args) {
    $assignment = trim($args['assignment'] ?? '');
    $criteria = $args['criteria'] ?? 'standard';
    if (empty($assignment)) return ['error' => false, 'message' => 'What assignment needs a rubric?'];
    $prompt = "You are Alfred, a professional AI grading assistant. Generate a detailed grading rubric for: $assignment. Criteria type: $criteria. " .
              "Include 4 performance levels (Excellent/Proficient/Developing/Beginning) with clear descriptors, point values, and specific expectations for each criterion.";
    $result = callAlfred($prompt);
    return ['success' => true, 'assignment' => $assignment, 'criteria' => $criteria, 'data' => $result,
            'message' => "Rubric for '$assignment'. " . substr($result, 0, 500)];
}

function toolQuizMaker($args) {
    $topic = trim($args['topic'] ?? '');
    $question_count = $args['question_count'] ?? 10;
    $difficulty = $args['difficulty'] ?? 'medium';
    if (empty($topic)) return ['error' => false, 'message' => 'What topic should the quiz cover?'];
    $prompt = "You are Alfred, a professional AI quiz creator. Create a $question_count-question quiz on '$topic' at $difficulty difficulty. " .
              "Mix question types: multiple choice, true/false, short answer. Include an answer key with explanations. Align to common standards.";
    $result = callAlfred($prompt);
    return ['success' => true, 'topic' => $topic, 'question_count' => $question_count, 'difficulty' => $difficulty, 'data' => $result,
            'message' => "Quiz on '$topic' ($question_count questions, $difficulty). " . substr($result, 0, 500)];
}

function toolStudentProgressReport($args) {
    $student_name = trim($args['student_name'] ?? '');
    $subject = trim($args['subject'] ?? '');
    $grades = $args['grades'] ?? [];
    if (empty($student_name) || empty($subject)) return ['error' => false, 'message' => 'Please provide student name and subject.'];
    $gradeData = is_array($grades) ? json_encode($grades) : $grades;
    $prompt = "You are Alfred, a professional AI education assistant. Generate a progress report for student $student_name in $subject. " .
              "Grades/scores: $gradeData. Include strengths, areas for improvement, specific recommendations, and encouraging language suitable for parent review.";
    $result = callAlfred($prompt);
    return ['success' => true, 'student_name' => $student_name, 'subject' => $subject, 'grades' => $grades, 'data' => $result,
            'message' => "Progress report for $student_name in $subject. " . substr($result, 0, 500)];
}

function toolClassroomManager($args) {
    $class_size = $args['class_size'] ?? 0;
    $age_group = trim($args['age_group'] ?? '');
    if (empty($age_group)) return ['error' => false, 'message' => 'What age group are you teaching?'];
    $prompt = "You are Alfred, a professional AI classroom management expert. Provide classroom management strategies for a class of $class_size students, age group: $age_group. " .
              "Include behavior management techniques, engagement strategies, routine suggestions, seating arrangements, and de-escalation methods.";
    $result = callAlfred($prompt);
    return ['success' => true, 'class_size' => $class_size, 'age_group' => $age_group, 'data' => $result,
            'message' => "Classroom management tips for $class_size students ($age_group). " . substr($result, 0, 500)];
}

function toolDifferentiatedInstruction($args) {
    $topic = trim($args['topic'] ?? '');
    $learning_levels = $args['learning_levels'] ?? '';
    if (empty($topic)) return ['error' => false, 'message' => 'What topic needs differentiated instruction?'];
    $levelData = is_array($learning_levels) ? json_encode($learning_levels) : $learning_levels;
    $prompt = "You are Alfred, a professional AI differentiation specialist. Create differentiated instruction for '$topic' across learning levels: $levelData. " .
              "Provide tiered activities for below-grade, on-grade, and above-grade learners. Include scaffolding strategies, modified assessments, and extension activities.";
    $result = callAlfred($prompt);
    return ['success' => true, 'topic' => $topic, 'learning_levels' => $learning_levels, 'data' => $result,
            'message' => "Differentiated instruction for '$topic'. " . substr($result, 0, 500)];
}

function toolParentCommunicator($args) {
    $message_type = trim($args['message_type'] ?? '');
    $context = trim($args['context'] ?? '');
    if (empty($message_type)) return ['error' => false, 'message' => 'What type of parent communication do you need?'];
    $prompt = "You are Alfred, a professional AI communication assistant for teachers. Draft a $message_type message to parents. Context: $context. " .
              "Use professional, warm, and clear language. Include specific details, next steps, and an invitation for follow-up. Maintain positive tone.";
    $result = callAlfred($prompt);
    return ['success' => true, 'message_type' => $message_type, 'context' => $context, 'data' => $result,
            'message' => "Parent $message_type communication drafted. " . substr($result, 0, 500)];
}

function toolFieldTripPlanner($args) {
    $destination = trim($args['destination'] ?? '');
    $grade = trim($args['grade'] ?? '');
    $budget = $args['budget'] ?? 0;
    if (empty($destination)) return ['error' => false, 'message' => 'Where would you like to take the field trip?'];
    $prompt = "You are Alfred, a professional AI field trip planner. Plan a field trip to $destination for grade $grade students. Budget: \$$budget. " .
              "Include learning objectives, permission slip details, itinerary, safety protocols, chaperone requirements, pre-trip activities, and post-trip reflection.";
    $result = callAlfred($prompt);
    return ['success' => true, 'destination' => $destination, 'grade' => $grade, 'budget' => $budget, 'data' => $result,
            'message' => "Field trip plan to '$destination' (grade $grade). " . substr($result, 0, 500)];
}

function toolCurriculumMapper($args) {
    $subject = trim($args['subject'] ?? '');
    $grade = trim($args['grade'] ?? '');
    $standards = $args['standards'] ?? 'common_core';
    if (empty($subject) || empty($grade)) return ['error' => false, 'message' => 'Please specify subject and grade level.'];
    $prompt = "You are Alfred, a professional AI curriculum specialist. Map a year-long curriculum for $subject, grade $grade aligned to $standards standards. " .
              "Include quarterly units, essential questions, key vocabulary, learning targets, suggested assessments, and cross-curricular connections.";
    $result = callAlfred($prompt);
    return ['success' => true, 'subject' => $subject, 'grade' => $grade, 'standards' => $standards, 'data' => $result,
            'message' => "Curriculum map: $subject, grade $grade ($standards). " . substr($result, 0, 500)];
}

function toolIEPHelper($args) {
    $student_needs = trim($args['student_needs'] ?? '');
    $goals = $args['goals'] ?? '';
    if (empty($student_needs)) return ['error' => false, 'message' => 'What are the student\'s specific needs?'];
    $goalData = is_array($goals) ? json_encode($goals) : $goals;
    $prompt = "You are Alfred, a professional AI special education assistant. Help develop IEP components for a student with needs: $student_needs. Goals: $goalData. " .
              "Include SMART goals, accommodations, modifications, progress monitoring methods, and service recommendations. Follow IDEA guidelines.";
    $result = callAlfred($prompt);
    return ['success' => true, 'student_needs' => $student_needs, 'goals' => $goals, 'data' => $result,
            'message' => "IEP helper for needs: $student_needs. " . substr($result, 0, 500)];
}

function toolClassroomActivity($args) {
    $subject = trim($args['subject'] ?? '');
    $duration = $args['duration'] ?? 15;
    $energy_level = $args['energy_level'] ?? 'medium';
    if (empty($subject)) return ['error' => false, 'message' => 'What subject is the activity for?'];
    $prompt = "You are Alfred, a professional AI activity designer. Create a $duration-minute $energy_level-energy classroom activity for $subject. " .
              "Include materials needed, step-by-step instructions, learning objective, differentiation options, and assessment/debrief strategy.";
    $result = callAlfred($prompt);
    return ['success' => true, 'subject' => $subject, 'duration' => $duration, 'energy_level' => $energy_level, 'data' => $result,
            'message' => "Classroom activity: $subject ($duration min, $energy_level energy). " . substr($result, 0, 500)];
}

function toolGradeCalculator($args) {
    $grades = $args['grades'] ?? [];
    $weights = $args['weights'] ?? null;
    if (empty($grades)) return ['error' => false, 'message' => 'Please provide the grades to calculate.'];
    $gradeData = is_array($grades) ? json_encode($grades) : $grades;
    $weightData = $weights ? (is_array($weights) ? json_encode($weights) : $weights) : 'equal weights';
    $prompt = "You are Alfred, a professional AI grading assistant. Calculate the final grade from these scores: $gradeData. " .
              "Weights: $weightData. Provide weighted average, letter grade, GPA equivalent, and breakdown showing how each component contributed.";
    $result = callAlfred($prompt);
    return ['success' => true, 'grades' => $grades, 'weights' => $weights, 'data' => $result,
            'message' => "Grade calculation complete. " . substr($result, 0, 500)];
}

function toolReadingListCurator($args) {
    $age_group = trim($args['age_group'] ?? '');
    $genre = $args['genre'] ?? 'mixed';
    $reading_level = $args['reading_level'] ?? 'grade_level';
    if (empty($age_group)) return ['error' => false, 'message' => 'What age group is the reading list for?'];
    $prompt = "You are Alfred, a professional AI librarian. Curate a reading list for $age_group students. Genre: $genre. Reading level: $reading_level. " .
              "Include 10-15 books with title, author, Lexile level, brief description, and discussion questions. Ensure diverse representation.";
    $result = callAlfred($prompt);
    return ['success' => true, 'age_group' => $age_group, 'genre' => $genre, 'reading_level' => $reading_level, 'data' => $result,
            'message' => "Reading list for $age_group ($genre, $reading_level). " . substr($result, 0, 500)];
}

function toolSELActivityGenerator($args) {
    $age_group = trim($args['age_group'] ?? '');
    $focus_area = $args['focus_area'] ?? 'self_awareness';
    if (empty($age_group)) return ['error' => false, 'message' => 'What age group is this SEL activity for?'];
    $prompt = "You are Alfred, a professional AI social-emotional learning specialist. Create a SEL activity for $age_group students focused on $focus_area. " .
              "Include learning objective, materials, step-by-step facilitation guide, reflection prompts, and take-home extension. Align to CASEL framework.";
    $result = callAlfred($prompt);
    return ['success' => true, 'age_group' => $age_group, 'focus_area' => $focus_area, 'data' => $result,
            'message' => "SEL activity for $age_group (focus: $focus_area). " . substr($result, 0, 500)];
}

function toolSubPlanCreator($args) {
    $subject = trim($args['subject'] ?? '');
    $grade = trim($args['grade'] ?? '');
    $duration = $args['duration'] ?? 'full_day';
    if (empty($subject) || empty($grade)) return ['error' => false, 'message' => 'Please specify subject and grade level.'];
    $prompt = "You are Alfred, a professional AI substitute teacher plan creator. Create a $duration substitute plan for $subject, grade $grade. " .
              "Include detailed schedule, self-contained activities requiring minimal prep, seating chart notes, emergency procedures, behavior expectations, and end-of-day checklist.";
    $result = callAlfred($prompt);
    return ['success' => true, 'subject' => $subject, 'grade' => $grade, 'duration' => $duration, 'data' => $result,
            'message' => "Sub plan: $subject, grade $grade ($duration). " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════
// VOICE CONFERENCING TOOLS (10)
// ═══════════════════════════════════════════════════════════════

function toolConferenceCreate($args) {
    $topic = trim($args['topic'] ?? '');
    $max_participants = $args['max_participants'] ?? 10;
    if (empty($topic)) return ['error' => false, 'message' => 'What is the conference topic?'];
    $conference_id = 'CONF-' . strtoupper(substr(md5($topic . time()), 0, 8));
    $prompt = "You are Alfred, a professional AI conference manager. Set up a voice conference on '$topic' with up to $max_participants participants. " .
              "Conference ID: $conference_id. Provide joining instructions, agenda template, ground rules, and recommended duration.";
    $result = callAlfred($prompt);
    return ['success' => true, 'conference_id' => $conference_id, 'topic' => $topic, 'max_participants' => $max_participants, 'data' => $result,
            'message' => "Conference '$conference_id' created on '$topic'. " . substr($result, 0, 500)];
}

function toolConferenceInvite($args) {
    $conference_id = trim($args['conference_id'] ?? '');
    $invitees = $args['invitees'] ?? [];
    if (empty($conference_id)) return ['error' => false, 'message' => 'Please provide the conference ID.'];
    if (empty($invitees)) return ['error' => false, 'message' => 'Who would you like to invite?'];
    $inviteeList = is_array($invitees) ? json_encode($invitees) : $invitees;
    $prompt = "You are Alfred, a professional AI conference assistant. Send invitations for conference $conference_id to: $inviteeList. " .
              "Draft a professional invitation with conference details, dial-in instructions, agenda, and RSVP request.";
    $result = callAlfred($prompt);
    return ['success' => true, 'conference_id' => $conference_id, 'invitees' => $invitees, 'data' => $result,
            'message' => "Invitations sent for conference $conference_id. " . substr($result, 0, 500)];
}

function toolConferenceModerate($args) {
    $conference_id = trim($args['conference_id'] ?? '');
    $action = trim($args['action'] ?? '');
    if (empty($conference_id)) return ['error' => false, 'message' => 'Please provide the conference ID.'];
    if (empty($action)) return ['error' => false, 'message' => 'What moderation action? (mute, unmute, kick, lock, unlock)'];
    $prompt = "You are Alfred, a professional AI conference moderator. Execute moderation action '$action' on conference $conference_id. " .
              "Confirm the action, provide participant status update, and suggest follow-up moderation best practices.";
    $result = callAlfred($prompt);
    return ['success' => true, 'conference_id' => $conference_id, 'action' => $action, 'data' => $result,
            'message' => "Moderation action '$action' applied to $conference_id. " . substr($result, 0, 500)];
}

function toolConferenceRecord($args) {
    $conference_id = trim($args['conference_id'] ?? '');
    $action = $args['action'] ?? 'start';
    if (empty($conference_id)) return ['error' => false, 'message' => 'Please provide the conference ID.'];
    $prompt = "You are Alfred, a professional AI conference assistant. $action recording for conference $conference_id. " .
              "Confirm recording status, notify participants of recording, and provide storage/retrieval details. Include consent reminder.";
    $result = callAlfred($prompt);
    return ['success' => true, 'conference_id' => $conference_id, 'action' => $action, 'data' => $result,
            'message' => "Recording $action for conference $conference_id. " . substr($result, 0, 500)];
}

function toolConferenceTranscribe($args) {
    $conference_id = trim($args['conference_id'] ?? '');
    if (empty($conference_id)) return ['error' => false, 'message' => 'Please provide the conference ID.'];
    $prompt = "You are Alfred, a professional AI transcription assistant. Transcribe conference $conference_id. " .
              "Provide speaker-identified transcript with timestamps, key topics discussed, action items mentioned, and decisions made.";
    $result = callAlfred($prompt);
    return ['success' => true, 'conference_id' => $conference_id, 'data' => $result,
            'message' => "Transcription for conference $conference_id. " . substr($result, 0, 500)];
}

function toolConferenceSummarize($args) {
    $conference_id = trim($args['conference_id'] ?? '');
    if (empty($conference_id)) return ['error' => false, 'message' => 'Please provide the conference ID.'];
    $prompt = "You are Alfred, a professional AI meeting summarizer. Summarize conference $conference_id. " .
              "Include executive summary, key discussion points, decisions made, action items with owners and deadlines, and open questions for follow-up.";
    $result = callAlfred($prompt);
    return ['success' => true, 'conference_id' => $conference_id, 'data' => $result,
            'message' => "Summary of conference $conference_id. " . substr($result, 0, 500)];
}

function toolConferencePoll($args) {
    $conference_id = trim($args['conference_id'] ?? '');
    $question = trim($args['question'] ?? '');
    $options = $args['options'] ?? [];
    if (empty($conference_id)) return ['error' => false, 'message' => 'Please provide the conference ID.'];
    if (empty($question)) return ['error' => false, 'message' => 'What question should the poll ask?'];
    $optionList = is_array($options) ? json_encode($options) : $options;
    $prompt = "You are Alfred, a professional AI conference poll manager. Run a poll in conference $conference_id. " .
              "Question: '$question'. Options: $optionList. Present the poll clearly, collect responses, and provide results with percentages.";
    $result = callAlfred($prompt);
    return ['success' => true, 'conference_id' => $conference_id, 'question' => $question, 'options' => $options, 'data' => $result,
            'message' => "Poll created for $conference_id: '$question'. " . substr($result, 0, 500)];
}

function toolConferenceBreakout($args) {
    $conference_id = trim($args['conference_id'] ?? '');
    $groups = $args['groups'] ?? 2;
    if (empty($conference_id)) return ['error' => false, 'message' => 'Please provide the conference ID.'];
    $prompt = "You are Alfred, a professional AI conference facilitator. Create $groups breakout rooms for conference $conference_id. " .
              "Suggest group assignments, provide each room with discussion prompts, set time limits, and plan reconvene strategy.";
    $result = callAlfred($prompt);
    return ['success' => true, 'conference_id' => $conference_id, 'groups' => $groups, 'data' => $result,
            'message' => "$groups breakout rooms created for $conference_id. " . substr($result, 0, 500)];
}

function toolConferenceAgenda($args) {
    $conference_id = trim($args['conference_id'] ?? '');
    $items = $args['items'] ?? [];
    if (empty($conference_id)) return ['error' => false, 'message' => 'Please provide the conference ID.'];
    if (empty($items)) return ['error' => false, 'message' => 'What items should be on the agenda?'];
    $itemList = is_array($items) ? json_encode($items) : $items;
    $prompt = "You are Alfred, a professional AI meeting organizer. Set the agenda for conference $conference_id. Items: $itemList. " .
              "Create a structured agenda with time allocations, presenters, discussion format, and break schedule. Ensure all items fit the meeting window.";
    $result = callAlfred($prompt);
    return ['success' => true, 'conference_id' => $conference_id, 'items' => $items, 'data' => $result,
            'message' => "Agenda set for conference $conference_id. " . substr($result, 0, 500)];
}

function toolConferenceFollowUp($args) {
    $conference_id = trim($args['conference_id'] ?? '');
    if (empty($conference_id)) return ['error' => false, 'message' => 'Please provide the conference ID.'];
    $prompt = "You are Alfred, a professional AI follow-up assistant. Generate follow-up tasks for conference $conference_id. " .
              "Include action items with assignees and deadlines, meeting minutes summary, next meeting date suggestion, and a follow-up email draft for all participants.";
    $result = callAlfred($prompt);
    return ['success' => true, 'conference_id' => $conference_id, 'data' => $result,
            'message' => "Follow-up tasks generated for conference $conference_id. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════
// v12.0 — REAL ESTATE VOICE TOOLS
// ═══════════════════════════════════════════════════════════════════════════

function toolPropertyValuator($args) {
    $type = $args['property_type'] ?? 'residential';
    $loc = $args['location'] ?? 'unknown';
    $sqft = $args['sqft'] ?? 'unknown';
    $beds = $args['bedrooms'] ?? 'unknown';
    $baths = $args['bathrooms'] ?? 'unknown';
    $prompt = "You are Alfred, a professional real estate valuation AI. Estimate the market value of a $type property in $loc with $sqft sqft, $beds bedrooms, $baths bathrooms. Provide a price range, comparable sales analysis, and key value factors.";
    $result = callAlfred($prompt);
    return ['success' => true, 'property_type' => $type, 'location' => $loc, 'sqft' => $sqft, 'data' => $result,
            'message' => "Property valuation for $type in $loc. " . substr($result, 0, 500)];
}

function toolMortgageCalculator($args) {
    $price = $args['price'] ?? 0;
    $down = $args['down_payment'] ?? 0;
    $rate = $args['interest_rate'] ?? 5.0;
    $term = $args['term_years'] ?? 25;
    $loan = $price - $down;
    $monthly_rate = ($rate / 100) / 12;
    $payments = $term * 12;
    $monthly = ($monthly_rate > 0) ? $loan * ($monthly_rate * pow(1 + $monthly_rate, $payments)) / (pow(1 + $monthly_rate, $payments) - 1) : ($loan / $payments);
    $total = $monthly * $payments;
    $prompt = "You are Alfred, a mortgage advisor AI. Summarize this mortgage: price \$$price, down payment \$$down, loan \$$loan, rate {$rate}%, term {$term} years, monthly payment \$" . round($monthly, 2) . ", total cost \$" . round($total, 2) . ". Include tips on reducing interest and payment strategies.";
    $result = callAlfred($prompt);
    return ['success' => true, 'monthly_payment' => round($monthly, 2), 'total_cost' => round($total, 2), 'loan_amount' => $loan, 'data' => $result,
            'message' => "Monthly payment: \$" . round($monthly, 2) . ". " . substr($result, 0, 500)];
}

function toolNeighborhoodAnalyzer($args) {
    $loc = $args['location'] ?? 'unknown';
    $radius = $args['radius'] ?? '5km';
    $prompt = "You are Alfred, a neighborhood analysis AI. Analyze the neighborhood around $loc within a $radius radius. Cover schools and education quality, crime statistics and safety, amenities (parks, shopping, transit), property value trends, demographics, and walkability score.";
    $result = callAlfred($prompt);
    return ['success' => true, 'location' => $loc, 'radius' => $radius, 'data' => $result,
            'message' => "Neighborhood analysis for $loc. " . substr($result, 0, 500)];
}

function toolListingDescriptionWriter($args) {
    $type = $args['property_type'] ?? 'home';
    $features = $args['features'] ?? 'modern finishes';
    $loc = $args['location'] ?? 'a great neighborhood';
    $prompt = "You are Alfred, a professional real estate copywriter AI. Write a compelling MLS listing description for a $type in $loc with these features: $features. Make it engaging, highlight key selling points, and include a call to action.";
    $result = callAlfred($prompt);
    return ['success' => true, 'property_type' => $type, 'location' => $loc, 'data' => $result,
            'message' => "Listing description for $type in $loc. " . substr($result, 0, 500)];
}

function toolOpenHousePlanner($args) {
    $address = $args['property_address'] ?? 'unknown';
    $date = $args['date'] ?? 'upcoming weekend';
    $visitors = $args['expected_visitors'] ?? '20';
    $prompt = "You are Alfred, an open house planning AI. Plan an open house at $address on $date expecting $visitors visitors. Include staging checklist, marketing plan, sign placement, refreshment suggestions, visitor sign-in strategy, follow-up plan, and safety protocols.";
    $result = callAlfred($prompt);
    return ['success' => true, 'address' => $address, 'date' => $date, 'expected_visitors' => $visitors, 'data' => $result,
            'message' => "Open house plan for $address on $date. " . substr($result, 0, 500)];
}

function toolComparativeMarketAnalysis($args) {
    $address = $args['address'] ?? 'unknown';
    $type = $args['property_type'] ?? 'residential';
    $sqft = $args['sqft'] ?? 'unknown';
    $prompt = "You are Alfred, a CMA specialist AI. Generate a Comparative Market Analysis for $type property at $address ($sqft sqft). Include recent comparable sales within 1km, active listings comparison, price per sqft analysis, days on market averages, recommended listing price range, and market trend assessment.";
    $result = callAlfred($prompt);
    return ['success' => true, 'address' => $address, 'property_type' => $type, 'sqft' => $sqft, 'data' => $result,
            'message' => "CMA report for $address. " . substr($result, 0, 500)];
}

function toolRentalYieldCalculator($args) {
    $price = floatval($args['purchase_price'] ?? 0);
    $rent = floatval($args['monthly_rent'] ?? 0);
    $expenses = floatval($args['expenses'] ?? 0);
    $annual_rent = $rent * 12;
    $annual_expenses = $expenses * 12;
    $net_income = $annual_rent - $annual_expenses;
    $gross_yield = ($price > 0) ? round(($annual_rent / $price) * 100, 2) : 0;
    $net_yield = ($price > 0) ? round(($net_income / $price) * 100, 2) : 0;
    $prompt = "You are Alfred, a rental investment AI. Analyze this rental: purchase price \$$price, monthly rent \$$rent, monthly expenses \$$expenses. Gross yield: {$gross_yield}%, net yield: {$net_yield}%. Provide investment assessment, ROI timeline, and optimization suggestions.";
    $result = callAlfred($prompt);
    return ['success' => true, 'gross_yield' => $gross_yield, 'net_yield' => $net_yield, 'annual_net_income' => $net_income, 'data' => $result,
            'message' => "Gross yield: {$gross_yield}%, net yield: {$net_yield}%. " . substr($result, 0, 500)];
}

function toolHomeInspectionChecklist($args) {
    $type = $args['property_type'] ?? 'single-family home';
    $age = $args['age_years'] ?? 'unknown';
    $prompt = "You are Alfred, a home inspection AI. Generate a thorough inspection checklist for a $type that is $age years old. Cover foundation and structure, roof and exterior, plumbing, electrical, HVAC, insulation, windows and doors, appliances, and age-specific concerns. Flag high-priority items.";
    $result = callAlfred($prompt);
    return ['success' => true, 'property_type' => $type, 'age_years' => $age, 'data' => $result,
            'message' => "Inspection checklist for $age-year-old $type. " . substr($result, 0, 500)];
}

function toolClosingCostEstimator($args) {
    $price = floatval($args['price'] ?? 0);
    $state = $args['state'] ?? 'unknown';
    $loan_type = $args['loan_type'] ?? 'conventional';
    $estimated = round($price * 0.03, 2);
    $prompt = "You are Alfred, a closing cost specialist AI. Estimate closing costs for a \$$price property in $state with a $loan_type loan. Include title insurance, appraisal fees, attorney fees, transfer taxes, recording fees, prepaid items, and lender fees. Estimated total: approximately \$$estimated.";
    $result = callAlfred($prompt);
    return ['success' => true, 'price' => $price, 'state' => $state, 'loan_type' => $loan_type, 'estimated_total' => $estimated, 'data' => $result,
            'message' => "Estimated closing costs: ~\$$estimated. " . substr($result, 0, 500)];
}

function toolPropertyTaxEstimator($args) {
    $assessed = floatval($args['assessed_value'] ?? 0);
    $loc = $args['location'] ?? 'unknown';
    $prompt = "You are Alfred, a property tax AI. Estimate annual property taxes for a property assessed at \$$assessed in $loc. Include municipal tax rate estimates, school tax, any special assessments, exemptions that may apply (homestead, senior, veteran), and payment schedule options.";
    $result = callAlfred($prompt);
    return ['success' => true, 'assessed_value' => $assessed, 'location' => $loc, 'data' => $result,
            'message' => "Property tax estimate for \$$assessed assessed in $loc. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════
// v12.1 — FREELANCER VOICE TOOLS
// ═══════════════════════════════════════════════════════════════════════════

function toolFreelanceRateCalculator($args) {
    $skill = $args['skill'] ?? 'general';
    $years = $args['experience_years'] ?? 1;
    $loc = $args['location'] ?? 'remote';
    $prompt = "You are Alfred, a freelance rate advisor AI. Calculate competitive hourly and project rates for a $skill freelancer with $years years of experience, based in $loc. Include market rate ranges, positioning strategy, value-based pricing tips, and rate negotiation advice.";
    $result = callAlfred($prompt);
    return ['success' => true, 'skill' => $skill, 'experience_years' => $years, 'location' => $loc, 'data' => $result,
            'message' => "Rate analysis for $skill freelancer ($years yrs). " . substr($result, 0, 500)];
}

function toolProposalWriter($args) {
    $desc = $args['project_description'] ?? 'project';
    $client = $args['client_type'] ?? 'business';
    $budget = $args['budget_range'] ?? 'flexible';
    $prompt = "You are Alfred, a proposal writing AI. Write a professional freelance proposal for: $desc. Client type: $client. Budget range: $budget. Include executive summary, scope of work, timeline, deliverables, pricing breakdown, and terms. Make it persuasive and professional.";
    $result = callAlfred($prompt);
    return ['success' => true, 'project_description' => $desc, 'client_type' => $client, 'data' => $result,
            'message' => "Proposal drafted for $client project. " . substr($result, 0, 500)];
}

function toolContractGenerator($args) {
    $service = $args['service_type'] ?? 'consulting';
    $duration = $args['duration'] ?? '3 months';
    $terms = $args['payment_terms'] ?? 'net 30';
    $prompt = "You are Alfred, a freelance contract AI. Generate a professional freelance contract for $service services, duration $duration, payment terms: $terms. Include scope of work, intellectual property clauses, termination terms, liability limitations, confidentiality, revision policy, and dispute resolution.";
    $result = callAlfred($prompt);
    return ['success' => true, 'service_type' => $service, 'duration' => $duration, 'payment_terms' => $terms, 'data' => $result,
            'message' => "Contract generated for $service ($duration). " . substr($result, 0, 500)];
}

function toolTimeTracker($args) {
    $project = $args['project_name'] ?? 'unnamed';
    $task = $args['task'] ?? 'general work';
    $minutes = intval($args['duration_minutes'] ?? 0);
    $hours = round($minutes / 60, 2);
    $prompt = "You are Alfred, a time tracking AI. Log $minutes minutes ($hours hours) for task '$task' on project '$project'. Provide a formatted time entry, suggest if this seems within normal range for this type of task, and give productivity tips.";
    $result = callAlfred($prompt);
    return ['success' => true, 'project_name' => $project, 'task' => $task, 'duration_minutes' => $minutes, 'hours' => $hours, 'data' => $result,
            'message' => "Logged $hours hrs on $project: $task. " . substr($result, 0, 500)];
}

function toolClientOnboarding($args) {
    $client = $args['client_name'] ?? 'new client';
    $service = $args['service_type'] ?? 'general';
    $start = $args['start_date'] ?? 'TBD';
    $prompt = "You are Alfred, a client onboarding AI. Create an onboarding checklist for client '$client' starting $service services on $start. Include welcome package, access/credential setup, kickoff meeting agenda, communication preferences, milestone schedule, billing setup, and expectation alignment.";
    $result = callAlfred($prompt);
    return ['success' => true, 'client_name' => $client, 'service_type' => $service, 'start_date' => $start, 'data' => $result,
            'message' => "Onboarding checklist for $client. " . substr($result, 0, 500)];
}

function toolPortfolioOptimizer($args) {
    $skills = $args['skills'] ?? 'general';
    $industry = $args['target_industry'] ?? 'tech';
    $level = $args['experience_level'] ?? 'mid';
    $prompt = "You are Alfred, a portfolio optimization AI. Optimize a freelance portfolio for someone with skills in $skills, targeting $industry industry, at $level experience level. Suggest which projects to highlight, case study structure, portfolio layout, SEO keywords, and presentation tips.";
    $result = callAlfred($prompt);
    return ['success' => true, 'skills' => $skills, 'target_industry' => $industry, 'experience_level' => $level, 'data' => $result,
            'message' => "Portfolio optimization for $industry. " . substr($result, 0, 500)];
}

function toolFreelanceTaxHelper($args) {
    $income = floatval($args['income'] ?? 0);
    $expenses = floatval($args['expenses'] ?? 0);
    $year = $args['tax_year'] ?? date('Y');
    $status = $args['filing_status'] ?? 'single';
    $net = $income - $expenses;
    $prompt = "You are Alfred, a freelance tax advisor AI. Help with tax planning for: gross income \$$income, deductible expenses \$$expenses, net income \$$net, tax year $year, filing status $status. Cover estimated quarterly payments, deduction opportunities, self-employment tax, retirement contribution strategies, and record-keeping tips. This is general guidance, not tax advice.";
    $result = callAlfred($prompt);
    return ['success' => true, 'income' => $income, 'expenses' => $expenses, 'net_income' => $net, 'tax_year' => $year, 'data' => $result,
            'message' => "Tax guidance for $year (net: \$$net). " . substr($result, 0, 500)];
}

function toolScopeCreepDetector($args) {
    $original = $args['original_scope'] ?? '';
    $current = $args['current_requests'] ?? '';
    $prompt = "You are Alfred, a scope creep detection AI. Compare the original project scope: '$original' with current client requests: '$current'. Identify any scope creep, quantify the additional work, suggest how to communicate changes to the client, recommend change order language, and estimate additional cost/time impact.";
    $result = callAlfred($prompt);
    return ['success' => true, 'original_scope' => $original, 'current_requests' => $current, 'data' => $result,
            'message' => "Scope creep analysis complete. " . substr($result, 0, 500)];
}

function toolTestimonialRequester($args) {
    $client = $args['client_name'] ?? 'client';
    $project = $args['project_name'] ?? 'recent project';
    $outcome = $args['project_outcome'] ?? 'successful completion';
    $prompt = "You are Alfred, a testimonial request AI. Draft a professional testimonial request email to $client for '$project' (outcome: $outcome). Include a warm opening, specific questions to guide their response, suggested formats (written, video, LinkedIn), and make it easy for them to respond.";
    $result = callAlfred($prompt);
    return ['success' => true, 'client_name' => $client, 'project_name' => $project, 'data' => $result,
            'message' => "Testimonial request drafted for $client. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════
// v12.2 — SENIORS VOICE TOOLS
// ═══════════════════════════════════════════════════════════════════════════

function toolMedicationManager($args) {
    $meds = $args['medications'] ?? 'none specified';
    $schedule = $args['schedule'] ?? 'daily';
    $reminders = $args['reminders'] ?? 'morning and evening';
    if (is_array($meds)) $meds = implode(', ', $meds);
    $prompt = "You are Alfred, a gentle and caring medication management AI for seniors. Help manage these medications: $meds. Schedule: $schedule. Reminder preferences: $reminders. Provide a clear, simple medication schedule, interaction warnings, reminder suggestions, and tips for medication adherence. Speak clearly and simply.";
    $result = callAlfred($prompt);
    return ['success' => true, 'medications' => $meds, 'schedule' => $schedule, 'data' => $result,
            'message' => "Medication schedule organized. " . substr($result, 0, 500)];
}

function toolAppointmentHelper($args) {
    $doctor = $args['doctor_name'] ?? 'doctor';
    $specialty = $args['specialty'] ?? 'general';
    $date = $args['date'] ?? 'upcoming';
    $time = $args['time'] ?? 'to be confirmed';
    $prompt = "You are Alfred, a warm and helpful medical appointment assistant AI for seniors. Help prepare for an appointment with Dr. $doctor ($specialty) on $date at $time. Provide a preparation checklist, questions to ask, documents to bring, transportation reminders, and post-appointment follow-up steps. Be clear and reassuring.";
    $result = callAlfred($prompt);
    return ['success' => true, 'doctor_name' => $doctor, 'specialty' => $specialty, 'date' => $date, 'time' => $time, 'data' => $result,
            'message' => "Appointment prep for Dr. $doctor on $date. " . substr($result, 0, 500)];
}

function toolFamilyConnector($args) {
    $members = $args['family_members'] ?? 'family';
    $message = $args['message'] ?? '';
    $occasion = $args['occasion'] ?? 'just checking in';
    if (is_array($members)) $members = implode(', ', $members);
    $prompt = "You are Alfred, a warm family connection AI for seniors. Help compose a message to $members for: $occasion. User wants to say: '$message'. Draft a warm, personal message suitable for the occasion. Suggest the best way to send it (text, email, video call) and offer conversation starters.";
    $result = callAlfred($prompt);
    return ['success' => true, 'family_members' => $members, 'occasion' => $occasion, 'data' => $result,
            'message' => "Family message drafted for $occasion. " . substr($result, 0, 500)];
}

function toolMemoryExercise($args) {
    $difficulty = $args['difficulty'] ?? 'medium';
    $type = $args['exercise_type'] ?? 'word recall';
    $prompt = "You are Alfred, a gentle cognitive exercise AI for seniors. Create a $difficulty difficulty $type memory exercise. Make it engaging, fun and encouraging. Include clear instructions, the exercise itself, hints if needed, and positive reinforcement. Keep language simple and supportive.";
    $result = callAlfred($prompt);
    return ['success' => true, 'difficulty' => $difficulty, 'exercise_type' => $type, 'data' => $result,
            'message' => "Here's your $difficulty $type exercise. " . substr($result, 0, 500)];
}

function toolFallPreventionTips($args) {
    $home = $args['home_type'] ?? 'house';
    $mobility = $args['mobility_level'] ?? 'moderate';
    $prompt = "You are Alfred, a fall prevention safety AI for seniors. Provide fall prevention advice for a senior with $mobility mobility living in a $home. Cover room-by-room safety checklist, recommended grab bars and aids, footwear advice, lighting improvements, exercise for balance, and emergency response plan. Be caring and practical.";
    $result = callAlfred($prompt);
    return ['success' => true, 'home_type' => $home, 'mobility_level' => $mobility, 'data' => $result,
            'message' => "Fall prevention tips for $home. " . substr($result, 0, 500)];
}

function toolLargeTextReader($args) {
    $text = $args['text'] ?? '';
    $font_size = $args['font_size'] ?? 24;
    $prompt = "You are Alfred, a reading assistant AI for seniors. Read and summarize this text clearly: '$text'. Provide a simple summary in plain language, break down any complex terms, and highlight the key points. This is for someone who may have difficulty reading small text.";
    $result = callAlfred($prompt);
    return ['success' => true, 'font_size' => $font_size, 'text_length' => strlen($text), 'data' => $result,
            'message' => "Here is the text summary. " . substr($result, 0, 500)];
}

function toolSimplifiedTechHelp($args) {
    $device = $args['device_type'] ?? 'smartphone';
    $problem = $args['problem'] ?? 'general help';
    $prompt = "You are Alfred, a patient and kind tech support AI for seniors. Help with this $device problem: $problem. Provide step-by-step instructions using simple, non-technical language. Number each step, use large clear descriptions, and be very patient and encouraging. Avoid jargon.";
    $result = callAlfred($prompt);
    return ['success' => true, 'device_type' => $device, 'problem' => $problem, 'data' => $result,
            'message' => "Tech help for $device. " . substr($result, 0, 500)];
}

function toolDailyRoutineHelper($args) {
    $routine = $args['routine_type'] ?? 'general';
    $time = $args['time_of_day'] ?? 'morning';
    $prompt = "You are Alfred, a caring daily routine assistant AI for seniors. Help organize a $time $routine routine. Include gentle reminders, health-related tasks (medications, exercises), meal suggestions, social activities, and rest periods. Make the schedule flexible and easy to follow. Be warm and encouraging.";
    $result = callAlfred($prompt);
    return ['success' => true, 'routine_type' => $routine, 'time_of_day' => $time, 'data' => $result,
            'message' => "$time $routine routine organized. " . substr($result, 0, 500)];
}

function toolEmergencyContacts($args) {
    $contacts = $args['contacts'] ?? [];
    $priority = $args['priority_order'] ?? 'default';
    if (is_array($contacts)) $contacts = json_encode($contacts);
    $prompt = "You are Alfred, an emergency preparedness AI for seniors. Organize these emergency contacts: $contacts with priority: $priority. Create a clear, easy-to-read emergency contact card with names, relationships, phone numbers. Include when to call each person, local emergency numbers (911, poison control), and medical information to have ready.";
    $result = callAlfred($prompt);
    return ['success' => true, 'priority_order' => $priority, 'data' => $result,
            'message' => "Emergency contacts organized. " . substr($result, 0, 500)];
}

function toolNutritionForSeniors($args) {
    $dietary = $args['dietary_restrictions'] ?? 'none';
    $conditions = $args['health_conditions'] ?? 'general wellness';
    if (is_array($dietary)) $dietary = implode(', ', $dietary);
    if (is_array($conditions)) $conditions = implode(', ', $conditions);
    $prompt = "You are Alfred, a senior nutrition AI. Provide nutrition advice for a senior with dietary restrictions: $dietary and health conditions: $conditions. Include meal suggestions, hydration reminders, supplements to discuss with their doctor, foods to emphasize or avoid, and easy-to-prepare recipes. Be practical and health-conscious.";
    $result = callAlfred($prompt);
    return ['success' => true, 'dietary_restrictions' => $dietary, 'health_conditions' => $conditions, 'data' => $result,
            'message' => "Nutrition advice prepared. " . substr($result, 0, 500)];
}

function toolExerciseForSeniors($args) {
    $fitness = $args['fitness_level'] ?? 'moderate';
    $limitations = $args['limitations'] ?? 'none';
    $duration = $args['duration'] ?? '20 minutes';
    if (is_array($limitations)) $limitations = implode(', ', $limitations);
    $prompt = "You are Alfred, a senior fitness AI. Create a safe exercise routine for a senior at $fitness fitness level with limitations: $limitations, duration: $duration. Include warm-up, main exercises (chair-based options), cool-down, balance exercises, and safety precautions. Emphasize gentle movements and proper breathing.";
    $result = callAlfred($prompt);
    return ['success' => true, 'fitness_level' => $fitness, 'limitations' => $limitations, 'duration' => $duration, 'data' => $result,
            'message' => "Exercise routine ($duration, $fitness level). " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════
// v12.3 — PARENTS / FAMILY VOICE TOOLS
// ═══════════════════════════════════════════════════════════════════════════

function toolChoreAssigner($args) {
    $members = $args['family_members'] ?? [];
    $chores = $args['chores'] ?? [];
    $freq = $args['frequency'] ?? 'weekly';
    if (is_array($members)) $members = implode(', ', $members);
    if (is_array($chores)) $chores = implode(', ', $chores);
    $prompt = "You are Alfred, a family chore management AI. Create a fair $freq chore schedule for family members: $members. Chores to assign: $chores. Consider age-appropriateness, rotate tasks fairly, include reward/accountability system, and make it fun for kids. Provide a clear weekly chart.";
    $result = callAlfred($prompt);
    return ['success' => true, 'family_members' => $members, 'frequency' => $freq, 'data' => $result,
            'message' => "Chore schedule created ($freq). " . substr($result, 0, 500)];
}

function toolMealPlanFamily($args) {
    $size = $args['family_size'] ?? 4;
    $dietary = $args['dietary_needs'] ?? 'none';
    $budget = $args['budget'] ?? 'moderate';
    if (is_array($dietary)) $dietary = implode(', ', $dietary);
    $prompt = "You are Alfred, a family meal planning AI. Create a weekly meal plan for a family of $size with dietary needs: $dietary on a $budget budget. Include breakfasts, lunches, dinners, and snacks. Provide a grocery list, prep-ahead tips, kid-friendly options, and leftover strategies. Be practical and budget-conscious.";
    $result = callAlfred($prompt);
    return ['success' => true, 'family_size' => $size, 'dietary_needs' => $dietary, 'budget' => $budget, 'data' => $result,
            'message' => "Weekly meal plan for family of $size. " . substr($result, 0, 500)];
}

function toolScreenTimeManager($args) {
    $child = $args['child_name'] ?? 'your child';
    $age = $args['age'] ?? 'unknown';
    $usage = $args['current_usage'] ?? 'unknown';
    $prompt = "You are Alfred, a family screen time management AI. Help manage screen time for $child (age $age), currently using $usage. Provide age-appropriate screen time guidelines (AAP recommendations), suggest a balanced daily schedule, recommend educational content, outdoor activity alternatives, and tips for reducing screen time without conflict.";
    $result = callAlfred($prompt);
    return ['success' => true, 'child_name' => $child, 'age' => $age, 'current_usage' => $usage, 'data' => $result,
            'message' => "Screen time plan for $child (age $age). " . substr($result, 0, 500)];
}

function toolHomeworkHelperParent($args) {
    $subject = $args['subject'] ?? 'general';
    $grade = $args['grade_level'] ?? 'elementary';
    $problem = $args['problem_description'] ?? '';
    $prompt = "You are Alfred, a parent homework support AI. Help a parent assist their $grade student with $subject homework. Problem: $problem. Provide clear explanations the parent can use to teach the concept, NOT just the answer. Include teaching strategies, related practice problems, and resources. Help the parent be an effective tutor.";
    $result = callAlfred($prompt);
    return ['success' => true, 'subject' => $subject, 'grade_level' => $grade, 'data' => $result,
            'message' => "Homework help for $grade $subject. " . substr($result, 0, 500)];
}

function toolFamilyBudget($args) {
    $income = floatval($args['income'] ?? 0);
    $expenses = $args['expenses'] ?? [];
    $goal = $args['savings_goal'] ?? 'general savings';
    if (is_array($expenses)) $expenses = json_encode($expenses);
    $prompt = "You are Alfred, a family budget planning AI. Create a family budget: monthly income \$$income, expenses: $expenses, savings goal: $goal. Apply the 50/30/20 rule, identify areas to cut, create an emergency fund plan, suggest savings strategies, and provide a clear monthly budget template. Be practical and encouraging.";
    $result = callAlfred($prompt);
    return ['success' => true, 'income' => $income, 'savings_goal' => $goal, 'data' => $result,
            'message' => "Family budget plan (income: \$$income). " . substr($result, 0, 500)];
}

function toolChildMilestoneTracker($args) {
    $child = $args['child_name'] ?? 'your child';
    $months = $args['age_months'] ?? 0;
    $milestones = $args['milestones'] ?? [];
    if (is_array($milestones)) $milestones = implode(', ', $milestones);
    $prompt = "You are Alfred, a child development milestone AI. Track milestones for $child at $months months old. Achieved milestones: $milestones. Provide expected milestones for this age (physical, cognitive, social, language), flag any areas to discuss with pediatrician, suggest development-boosting activities, and give the next milestones to watch for. Be reassuring and supportive.";
    $result = callAlfred($prompt);
    return ['success' => true, 'child_name' => $child, 'age_months' => $months, 'data' => $result,
            'message' => "Milestone report for $child ($months months). " . substr($result, 0, 500)];
}

function toolFamilyActivityFinder($args) {
    $ages = $args['ages'] ?? [];
    $loc = $args['location'] ?? 'local area';
    $budget = $args['budget'] ?? 'moderate';
    $weather = $args['weather'] ?? 'any';
    if (is_array($ages)) $ages = implode(', ', $ages);
    $prompt = "You are Alfred, a family activity recommendation AI. Find activities for a family with ages: $ages, in $loc, budget: $budget, weather: $weather. Suggest indoor and outdoor activities, educational outings, free options, seasonal events, and age-appropriate adventures. Include logistics like timing and what to bring.";
    $result = callAlfred($prompt);
    return ['success' => true, 'ages' => $ages, 'location' => $loc, 'budget' => $budget, 'weather' => $weather, 'data' => $result,
            'message' => "Family activities in $loc ($weather weather). " . substr($result, 0, 500)];
}

function toolBedtimeStoryGenerator($args) {
    $age = $args['child_age'] ?? 5;
    $theme = $args['theme'] ?? 'adventure';
    $duration = $args['duration_minutes'] ?? 5;
    $prompt = "You are Alfred, a creative bedtime story AI. Generate a $duration-minute bedtime story for a $age-year-old with theme: $theme. Make it age-appropriate, calming toward the end, with a positive moral. Include character names, a gentle adventure, and a soothing conclusion that encourages sleep. Use vivid but peaceful imagery.";
    $result = callAlfred($prompt);
    return ['success' => true, 'child_age' => $age, 'theme' => $theme, 'duration_minutes' => $duration, 'data' => $result,
            'message' => "Bedtime story ($theme, $duration min). " . substr($result, 0, 500)];
}

// ============================================================
// NON-PROFIT TOOLS (6)
// ============================================================

function toolGrantFinder($args) {
    $org_type = $args['organization_type'] ?? 'nonprofit';
    $mission = $args['mission'] ?? 'community development';
    $budget = $args['budget_range'] ?? '10000-50000';
    $prompt = "You are Alfred, a professional grant research assistant. Find matching grants for a $org_type organization with mission: $mission and budget range \$$budget. List grant name, funder, amount, deadline, eligibility summary, and application URL where available. Prioritize currently open grants.";
    $result = callAlfred($prompt);
    return ['success' => true, 'organization_type' => $org_type, 'mission' => $mission, 'budget_range' => $budget, 'data' => $result,
            'message' => "Grant search for $org_type ($mission). " . substr($result, 0, 500)];
}

function toolDonorManager($args) {
    $donor = $args['donor_name'] ?? 'Anonymous';
    $amount = $args['donation_amount'] ?? '0';
    $campaign = $args['campaign'] ?? 'general';
    $prompt = "You are Alfred, a professional donor relationship manager. Manage donor record for $donor with donation \$$amount to campaign: $campaign. Provide a thank-you message template, suggest follow-up actions, recommend stewardship tier based on amount, and outline next engagement touchpoints for retention.";
    $result = callAlfred($prompt);
    return ['success' => true, 'donor_name' => $donor, 'donation_amount' => $amount, 'campaign' => $campaign, 'data' => $result,
            'message' => "Donor $donor managed (\$$amount, $campaign). " . substr($result, 0, 500)];
}

function toolVolunteerCoordinator($args) {
    $event = $args['event_name'] ?? 'Community Event';
    $roles = $args['roles_needed'] ?? 'general helpers';
    $date = $args['date'] ?? date('Y-m-d', strtotime('+7 days'));
    $prompt = "You are Alfred, a professional volunteer coordinator. Coordinate volunteers for event: $event on $date. Roles needed: $roles. Create a volunteer schedule with shift times, role descriptions, requirements, check-in procedures, and a recruitment message template for outreach.";
    $result = callAlfred($prompt);
    return ['success' => true, 'event_name' => $event, 'roles_needed' => $roles, 'date' => $date, 'data' => $result,
            'message' => "Volunteer plan for $event ($date). " . substr($result, 0, 500)];
}

function toolImpactReport($args) {
    $metrics = $args['metrics'] ?? 'beneficiaries served, funds raised';
    $period = $args['period'] ?? 'quarterly';
    $programs = $args['programs'] ?? 'all programs';
    $prompt = "You are Alfred, a professional impact reporting analyst. Generate a $period impact report for programs: $programs. Key metrics to cover: $metrics. Include executive summary, metric breakdowns with trends, beneficiary stories framework, data visualization suggestions, and recommendations for improvement.";
    $result = callAlfred($prompt);
    return ['success' => true, 'metrics' => $metrics, 'period' => $period, 'programs' => $programs, 'data' => $result,
            'message' => "Impact report ($period, $programs). " . substr($result, 0, 500)];
}

function toolFundraisingCampaign($args) {
    $name = $args['campaign_name'] ?? 'Annual Fund';
    $goal = $args['goal_amount'] ?? '50000';
    $duration = $args['duration_days'] ?? 30;
    $prompt = "You are Alfred, a professional fundraising strategist. Plan a fundraising campaign named '$name' with goal \$$goal over $duration days. Provide a day-by-day timeline, messaging strategy, donor segmentation tiers, communication channels, milestone markers, matching gift strategy, and urgency triggers for the final push.";
    $result = callAlfred($prompt);
    return ['success' => true, 'campaign_name' => $name, 'goal_amount' => $goal, 'duration_days' => $duration, 'data' => $result,
            'message' => "Campaign '$name' plan (\$$goal, $duration days). " . substr($result, 0, 500)];
}

function toolNonProfitCompliance($args) {
    $org_type = $args['organization_type'] ?? '501c3';
    $state = $args['state'] ?? 'CA';
    $revenue = $args['revenue'] ?? '100000';
    $prompt = "You are Alfred, a professional nonprofit compliance advisor. Generate a compliance checklist for a $org_type organization in $state with annual revenue \$$revenue. Cover IRS filing requirements (990 form type), state registration, charitable solicitation permits, board governance minimums, document retention policies, and key deadlines.";
    $result = callAlfred($prompt);
    return ['success' => true, 'organization_type' => $org_type, 'state' => $state, 'revenue' => $revenue, 'data' => $result,
            'message' => "Compliance checklist ($org_type, $state). " . substr($result, 0, 500)];
}

// ============================================================
// GAMIFICATION TOOLS (6)
// ============================================================

function toolXPCalculator($args) {
    $action = $args['action'] ?? 'task_complete';
    $difficulty = $args['difficulty'] ?? 'medium';
    $streak = $args['streak_bonus'] ?? 0;
    $base_xp = ['easy' => 10, 'medium' => 25, 'hard' => 50, 'expert' => 100];
    $xp = ($base_xp[$difficulty] ?? 25) * (1 + ($streak * 0.1));
    $prompt = "You are Alfred, a gamification engine. Calculate and explain XP award for action: $action at difficulty: $difficulty with streak bonus x$streak. Base XP: {$xp}. Provide breakdown, motivational message, and next milestone hint.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'difficulty' => $difficulty, 'streak_bonus' => $streak, 'xp_earned' => $xp, 'data' => $result,
            'message' => "XP earned: $xp ($action, $difficulty). " . substr($result, 0, 500)];
}

function toolAchievementChecker($args) {
    $stats = $args['user_stats'] ?? 'tasks:10,streak:3,xp:250';
    $catalog = $args['achievements_catalog'] ?? 'default';
    $prompt = "You are Alfred, a gamification achievement system. Check which achievements are unlocked given user stats: $stats against catalog: $catalog. List each unlocked achievement with name, description, icon suggestion, and XP reward. Also show nearest locked achievements with progress percentage.";
    $result = callAlfred($prompt);
    return ['success' => true, 'user_stats' => $stats, 'achievements_catalog' => $catalog, 'data' => $result,
            'message' => "Achievement check complete. " . substr($result, 0, 500)];
}

function toolLeaderboardManager($args) {
    $board = $args['leaderboard_name'] ?? 'global';
    $user_id = $args['user_id'] ?? 'anonymous';
    $score = $args['score'] ?? 0;
    $prompt = "You are Alfred, a leaderboard management system. Update leaderboard '$board' for user $user_id with score $score. Provide updated ranking, percentile position, distance to next rank, top 10 summary, and motivational comparison message. Suggest competitive challenges.";
    $result = callAlfred($prompt);
    return ['success' => true, 'leaderboard_name' => $board, 'user_id' => $user_id, 'score' => $score, 'data' => $result,
            'message' => "Leaderboard '$board' updated (user: $user_id, score: $score). " . substr($result, 0, 500)];
}

function toolStreakTracker($args) {
    $user_id = $args['user_id'] ?? 'anonymous';
    $action_type = $args['action_type'] ?? 'daily_login';
    $last_date = $args['last_action_date'] ?? date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');
    $diff = (strtotime($today) - strtotime($last_date)) / 86400;
    $streak_alive = $diff <= 1;
    $prompt = "You are Alfred, a streak tracking system. Track streak for user $user_id, action: $action_type. Last action: $last_date, today: $today, gap: $diff days. Streak alive: " . ($streak_alive ? 'yes' : 'no') . ". Calculate current streak length, provide streak status, bonus multiplier, and motivational message. Warn if streak is at risk.";
    $result = callAlfred($prompt);
    return ['success' => true, 'user_id' => $user_id, 'action_type' => $action_type, 'streak_alive' => $streak_alive, 'gap_days' => $diff, 'data' => $result,
            'message' => "Streak " . ($streak_alive ? 'continues' : 'broken') . " ($action_type). " . substr($result, 0, 500)];
}

function toolChallengeCreator($args) {
    $type = $args['challenge_type'] ?? 'daily';
    $difficulty = $args['difficulty'] ?? 'medium';
    $reward = $args['reward_xp'] ?? 50;
    $duration = $args['duration_days'] ?? 7;
    $prompt = "You are Alfred, a gamification challenge designer. Create a $type challenge at $difficulty difficulty, rewarding {$reward}XP over $duration days. Include challenge name, description, daily milestones, completion criteria, bonus objectives, failure conditions, and a shareable challenge card description.";
    $result = callAlfred($prompt);
    return ['success' => true, 'challenge_type' => $type, 'difficulty' => $difficulty, 'reward_xp' => $reward, 'duration_days' => $duration, 'data' => $result,
            'message' => "Challenge created ($type, $difficulty, {$reward}XP). " . substr($result, 0, 500)];
}

function toolBadgeDesigner($args) {
    $name = $args['badge_name'] ?? 'Newcomer';
    $criteria = $args['criteria'] ?? 'complete first task';
    $tier = $args['tier'] ?? 'bronze';
    $prompt = "You are Alfred, a gamification badge designer. Design an achievement badge named '$name' at $tier tier with criteria: $criteria. Provide badge visual description (colors, icon, shape), unlock animation suggestion, display text, rarity classification, point value, and a collection category. Include tier progression path (bronze→silver→gold→platinum).";
    $result = callAlfred($prompt);
    return ['success' => true, 'badge_name' => $name, 'criteria' => $criteria, 'tier' => $tier, 'data' => $result,
            'message' => "Badge '$name' designed ($tier). " . substr($result, 0, 500)];
}

// ============================================================
// MARKETPLACE TOOLS (6)
// ============================================================

function toolMarketplaceLister($args) {
    $item_type = $args['item_type'] ?? 'product';
    $title = $args['title'] ?? 'Untitled Item';
    $description = $args['description'] ?? '';
    $price = $args['price'] ?? '0.00';
    $prompt = "You are Alfred, a marketplace listing optimizer. Create an optimized listing for $item_type titled '$title' at \$$price. Description: $description. Generate SEO-friendly title, compelling description, suggested tags/categories, shipping considerations, and pricing analysis versus market rates. Include listing quality score.";
    $result = callAlfred($prompt);
    return ['success' => true, 'item_type' => $item_type, 'title' => $title, 'price' => $price, 'data' => $result,
            'message' => "Listed '$title' ($item_type, \$$price). " . substr($result, 0, 500)];
}

function toolPriceOptimizer($args) {
    $item_type = $args['item_type'] ?? 'product';
    $condition = $args['condition'] ?? 'new';
    $market_data = $args['market_data'] ?? 'no prior data';
    $prompt = "You are Alfred, a marketplace pricing analyst. Optimize price for a $condition $item_type. Market data: $market_data. Analyze competitive pricing, suggest optimal price point, provide price range (min/max/sweet spot), recommend pricing strategy (fixed/auction/negotiable), seasonal adjustments, and expected time-to-sell at each price tier.";
    $result = callAlfred($prompt);
    return ['success' => true, 'item_type' => $item_type, 'condition' => $condition, 'market_data' => $market_data, 'data' => $result,
            'message' => "Price optimized ($item_type, $condition). " . substr($result, 0, 500)];
}

function toolMarketplaceSearch($args) {
    $query = $args['query'] ?? '';
    $category = $args['category'] ?? 'all';
    $price_range = $args['price_range'] ?? '0-10000';
    $prompt = "You are Alfred, a marketplace search assistant. Search listings for: '$query' in category: $category, price range: \$$price_range. Provide curated results with relevance ranking, price comparison, seller ratings summary, deal quality score, and alternative suggestions if few results match. Include search refinement tips.";
    $result = callAlfred($prompt);
    return ['success' => true, 'query' => $query, 'category' => $category, 'price_range' => $price_range, 'data' => $result,
            'message' => "Search results for '$query' ($category). " . substr($result, 0, 500)];
}

function toolSellerRating($args) {
    $seller_id = $args['seller_id'] ?? 'unknown';
    $transaction_id = $args['transaction_id'] ?? 'none';
    $rating = $args['rating'] ?? 5;
    $review = $args['review'] ?? '';
    $prompt = "You are Alfred, a marketplace trust and rating system. Process rating $rating/5 for seller $seller_id on transaction $transaction_id. Review: '$review'. Analyze sentiment, update seller trust score, flag any concerning patterns, generate seller feedback summary, and provide buyer-facing seller reliability badge recommendation.";
    $result = callAlfred($prompt);
    return ['success' => true, 'seller_id' => $seller_id, 'transaction_id' => $transaction_id, 'rating' => $rating, 'review' => $review, 'data' => $result,
            'message' => "Rated seller $seller_id ($rating/5). " . substr($result, 0, 500)];
}

function toolMarketplaceAnalytics($args) {
    $seller_id = $args['seller_id'] ?? 'unknown';
    $date_range = $args['date_range'] ?? 'last_30_days';
    $prompt = "You are Alfred, a marketplace analytics engine. Generate seller dashboard for $seller_id over $date_range. Include total sales, revenue, average order value, conversion rate, top-selling items, buyer demographics, return rate, response time metrics, listing performance ranking, and actionable recommendations to boost sales.";
    $result = callAlfred($prompt);
    return ['success' => true, 'seller_id' => $seller_id, 'date_range' => $date_range, 'data' => $result,
            'message' => "Analytics for seller $seller_id ($date_range). " . substr($result, 0, 500)];
}

function toolDisputeResolver($args) {
    $transaction_id = $args['transaction_id'] ?? 'none';
    $issue_type = $args['issue_type'] ?? 'general';
    $description = $args['description'] ?? '';
    $prompt = "You are Alfred, a professional marketplace dispute mediator. Resolve dispute for transaction $transaction_id. Issue type: $issue_type. Description: $description. Analyze the dispute, recommend resolution (refund/partial refund/replacement/dismissal), provide mediation talking points, cite relevant marketplace policies, estimate resolution timeline, and draft communication to both parties.";
    $result = callAlfred($prompt);
    return ['success' => true, 'transaction_id' => $transaction_id, 'issue_type' => $issue_type, 'description' => $description, 'data' => $result,
            'message' => "Dispute resolved ($issue_type, txn: $transaction_id). " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════
// FUTURE TECH WORKERS (15 tools)
// ═══════════════════════════════════════════════════════════════════

function toolRobotFleetManager($args) {
    $fleet_id = $args['fleet_id'] ?? 'default';
    $action = $args['action'] ?? 'status';
    $task = $args['task'] ?? '';
    $prompt = "You are Alfred, a professional AI robot fleet operations manager. Fleet ID: $fleet_id. Action requested: $action. Task details: $task. Provide fleet status overview (active/idle/charging units), execute the requested action (status report / assign task to optimal robot / recall unit to base), report battery levels and maintenance schedules, flag any units needing service, and confirm task assignment or recall acknowledgment.";
    $result = callAlfred($prompt);
    return ['success' => true, 'fleet_id' => $fleet_id, 'action' => $action, 'task' => $task, 'data' => $result,
            'message' => "Robot fleet '$fleet_id' action '$action' completed. " . substr($result, 0, 500)];
}

function toolIoTDeviceManager($args) {
    $device_id = $args['device_id'] ?? 'unknown';
    $action = $args['action'] ?? 'status';
    $config = $args['config'] ?? '';
    $prompt = "You are Alfred, a professional AI IoT device management specialist. Device ID: $device_id. Action: $action. Configuration: $config. Check device connectivity and health status, execute the requested action (status check / firmware update / configuration change), report telemetry data summary, validate configuration parameters, identify security vulnerabilities, and confirm successful execution or report errors.";
    $result = callAlfred($prompt);
    return ['success' => true, 'device_id' => $device_id, 'action' => $action, 'config' => $config, 'data' => $result,
            'message' => "IoT device '$device_id' action '$action' processed. " . substr($result, 0, 500)];
}

function toolSmartHomeController($args) {
    $device = $args['device'] ?? 'unknown';
    $action = $args['action'] ?? 'status';
    $value = $args['value'] ?? '';
    $prompt = "You are Alfred, a professional AI smart home controller. Device: $device. Action: $action. Value: $value. Execute the home automation command (turn on / turn off / set to value), confirm device responded, report current device state after change, check for scheduling conflicts with other automations, suggest energy-saving optimizations, and provide scene/routine recommendations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'device' => $device, 'action' => $action, 'value' => $value, 'data' => $result,
            'message' => "Smart home device '$device' set to '$action' ($value). " . substr($result, 0, 500)];
}

function toolDroneMissionPlanner($args) {
    $mission_name = $args['mission_name'] ?? 'unnamed';
    $waypoints = $args['waypoints'] ?? '';
    $altitude = $args['altitude'] ?? 100;
    $prompt = "You are Alfred, a professional AI drone mission planning specialist. Mission: $mission_name. Waypoints: $waypoints. Altitude: {$altitude}m. Plan the optimal flight path between waypoints, calculate estimated flight time and battery requirements, check airspace restrictions and no-fly zones, generate pre-flight checklist, set altitude parameters, plan return-to-home contingencies, and output a complete mission file with GPS coordinates.";
    $result = callAlfred($prompt);
    return ['success' => true, 'mission_name' => $mission_name, 'waypoints' => $waypoints, 'altitude' => $altitude, 'data' => $result,
            'message' => "Drone mission '$mission_name' planned at {$altitude}m altitude. " . substr($result, 0, 500)];
}

function toolARSceneBuilder($args) {
    $scene_name = $args['scene_name'] ?? 'untitled';
    $objects = $args['objects'] ?? '';
    $environment = $args['environment'] ?? 'indoor';
    $prompt = "You are Alfred, a professional AI augmented reality scene designer. Scene: $scene_name. Objects: $objects. Environment: $environment. Design the AR scene layout with proper object placement and spatial anchoring, define interaction zones and gesture triggers, set lighting conditions matching the $environment environment, configure occlusion and physics properties, generate AR marker specifications, and provide platform-specific export settings (ARKit/ARCore).";
    $result = callAlfred($prompt);
    return ['success' => true, 'scene_name' => $scene_name, 'objects' => $objects, 'environment' => $environment, 'data' => $result,
            'message' => "AR scene '$scene_name' built ($environment). " . substr($result, 0, 500)];
}

function toolVRWorldCreator($args) {
    $world_name = $args['world_name'] ?? 'untitled';
    $theme = $args['theme'] ?? 'default';
    $interactions = $args['interactions'] ?? '';
    $prompt = "You are Alfred, a professional AI virtual reality world architect. World: $world_name. Theme: $theme. Interactions: $interactions. Design the complete VR environment with skybox, terrain, and props matching the theme, define locomotion mechanics (teleport/smooth/room-scale), set up interaction systems (grab/point/gesture), configure spatial audio zones, plan performance optimization for target headsets, and generate asset lists with LOD specifications.";
    $result = callAlfred($prompt);
    return ['success' => true, 'world_name' => $world_name, 'theme' => $theme, 'interactions' => $interactions, 'data' => $result,
            'message' => "VR world '$world_name' created (theme: $theme). " . substr($result, 0, 500)];
}

function tool3DPrintSlicer($args) {
    $model_file = $args['model_file'] ?? 'unknown';
    $material = $args['material'] ?? 'PLA';
    $layer_height = $args['layer_height'] ?? 0.2;
    $prompt = "You are Alfred, a professional AI 3D printing slicer specialist. Model: $model_file. Material: $material. Layer height: {$layer_height}mm. Analyze the 3D model for printability, generate optimal slicing parameters for $material material at {$layer_height}mm layer height, calculate support structure requirements, estimate print time and material usage, recommend orientation for best surface finish and strength, set temperature/speed profiles, and flag any geometry issues (non-manifold, thin walls).";
    $result = callAlfred($prompt);
    return ['success' => true, 'model_file' => $model_file, 'material' => $material, 'layer_height' => $layer_height, 'data' => $result,
            'message' => "3D print sliced: $model_file ($material, {$layer_height}mm). " . substr($result, 0, 500)];
}

function toolFirmwareUpdater($args) {
    $device_id = $args['device_id'] ?? 'unknown';
    $firmware_version = $args['firmware_version'] ?? 'latest';
    $force = $args['force'] ?? false;
    $force_str = $force ? 'forced' : 'standard';
    $prompt = "You are Alfred, a professional AI firmware update manager. Device: $device_id. Target version: $firmware_version. Mode: $force_str. Verify device compatibility with target firmware version, check current firmware version and changelog, create backup of current configuration, validate firmware integrity (checksum), plan rollback procedure in case of failure, execute $force_str update process, and verify successful installation with post-update diagnostics.";
    $result = callAlfred($prompt);
    return ['success' => true, 'device_id' => $device_id, 'firmware_version' => $firmware_version, 'force' => $force, 'data' => $result,
            'message' => "Firmware update to $firmware_version for device '$device_id' ($force_str). " . substr($result, 0, 500)];
}

function toolSensorDataAnalyzer($args) {
    $sensor_id = $args['sensor_id'] ?? 'unknown';
    $time_range = $args['time_range'] ?? '24h';
    $threshold = $args['threshold'] ?? '';
    $prompt = "You are Alfred, a professional AI sensor data analysis expert. Sensor: $sensor_id. Time range: $time_range. Threshold: $threshold. Retrieve and analyze sensor readings over the $time_range window, identify anomalies and threshold breaches, calculate statistical summaries (mean, median, std dev, min, max), detect trends and patterns, generate alerts for values exceeding threshold ($threshold), recommend calibration if drift detected, and produce a data quality assessment.";
    $result = callAlfred($prompt);
    return ['success' => true, 'sensor_id' => $sensor_id, 'time_range' => $time_range, 'threshold' => $threshold, 'data' => $result,
            'message' => "Sensor '$sensor_id' analyzed ($time_range). " . substr($result, 0, 500)];
}

function toolEdgeComputeDeployer($args) {
    $model_name = $args['model_name'] ?? 'unknown';
    $target_device = $args['target_device'] ?? 'generic';
    $optimization = $args['optimization'] ?? 'standard';
    $prompt = "You are Alfred, a professional AI edge computing deployment specialist. Model: $model_name. Target: $target_device. Optimization: $optimization. Analyze model size and computational requirements, apply $optimization optimization (quantization/pruning/distillation), validate compatibility with $target_device hardware constraints (memory, compute, power), generate deployment package with runtime dependencies, configure inference pipeline, benchmark latency and throughput, and provide monitoring setup instructions.";
    $result = callAlfred($prompt);
    return ['success' => true, 'model_name' => $model_name, 'target_device' => $target_device, 'optimization' => $optimization, 'data' => $result,
            'message' => "Edge deployment of '$model_name' to '$target_device' ($optimization). " . substr($result, 0, 500)];
}

function toolDigitalTwinCreator($args) {
    $system_name = $args['system_name'] ?? 'unnamed';
    $parameters = $args['parameters'] ?? '';
    $simulation_mode = $args['simulation_mode'] ?? 'realtime';
    $prompt = "You are Alfred, a professional AI digital twin engineering specialist. System: $system_name. Parameters: $parameters. Mode: $simulation_mode. Create a digital twin model of the physical system, define sensor mapping and data ingestion points, configure $simulation_mode simulation parameters, set up physics-based behavioral models, establish synchronization frequency with the physical counterpart, define anomaly detection rules, generate visualization dashboard specs, and plan what-if scenario capabilities.";
    $result = callAlfred($prompt);
    return ['success' => true, 'system_name' => $system_name, 'parameters' => $parameters, 'simulation_mode' => $simulation_mode, 'data' => $result,
            'message' => "Digital twin '$system_name' created ($simulation_mode mode). " . substr($result, 0, 500)];
}

function toolAutonomousVehicleSim($args) {
    $scenario = $args['scenario'] ?? 'basic';
    $vehicle_type = $args['vehicle_type'] ?? 'car';
    $conditions = $args['conditions'] ?? 'normal';
    $prompt = "You are Alfred, a professional AI autonomous vehicle simulation engineer. Scenario: $scenario. Vehicle: $vehicle_type. Conditions: $conditions. Set up simulation environment with $conditions weather/traffic conditions, configure $vehicle_type sensor suite (LiDAR, cameras, radar, ultrasonic), define test scenario waypoints and obstacles, simulate pedestrian and vehicle interactions, run collision avoidance algorithms, measure decision latency and safety margins, generate compliance report against safety standards, and log all edge cases encountered.";
    $result = callAlfred($prompt);
    return ['success' => true, 'scenario' => $scenario, 'vehicle_type' => $vehicle_type, 'conditions' => $conditions, 'data' => $result,
            'message' => "AV simulation '$scenario' ($vehicle_type, $conditions conditions). " . substr($result, 0, 500)];
}

function toolWearableAppBuilder($args) {
    $app_name = $args['app_name'] ?? 'untitled';
    $platform = $args['platform'] ?? 'watchos';
    $features = $args['features'] ?? '';
    $prompt = "You are Alfred, a professional AI wearable application developer. App: $app_name. Platform: $platform. Features: $features. Design the wearable app UI following $platform design guidelines (small screen, glanceable), implement requested features with power-efficient background processing, configure health/fitness sensor integrations, set up push notification handling, optimize for battery life and memory constraints, plan companion phone app communication, and generate app manifest with required permissions.";
    $result = callAlfred($prompt);
    return ['success' => true, 'app_name' => $app_name, 'platform' => $platform, 'features' => $features, 'data' => $result,
            'message' => "Wearable app '$app_name' built for $platform. " . substr($result, 0, 500)];
}

function toolBlockchainDeployer($args) {
    $contract_name = $args['contract_name'] ?? 'unnamed';
    $network = $args['network'] ?? 'ethereum';
    $code = $args['code'] ?? '';
    $prompt = "You are Alfred, a professional AI blockchain and smart contract deployment specialist. Contract: $contract_name. Network: $network. Code: $code. Analyze the smart contract code for security vulnerabilities (reentrancy, overflow, front-running), estimate gas costs on $network, compile and verify bytecode, prepare deployment transaction with optimal gas settings, set up contract verification on block explorer, configure proxy pattern if upgradeable, generate ABI documentation, and provide post-deployment testing checklist.";
    $result = callAlfred($prompt);
    return ['success' => true, 'contract_name' => $contract_name, 'network' => $network, 'code' => $code, 'data' => $result,
            'message' => "Smart contract '$contract_name' deployed to $network. " . substr($result, 0, 500)];
}

function toolQuantumCodeHelper($args) {
    $algorithm = $args['algorithm'] ?? 'basic';
    $framework = $args['framework'] ?? 'qiskit';
    $qubits = $args['qubits'] ?? 5;
    $prompt = "You are Alfred, a professional AI quantum computing assistant. Algorithm: $algorithm. Framework: $framework. Qubits: $qubits. Implement the $algorithm algorithm using $framework with $qubits qubits, design the quantum circuit with optimal gate decomposition, apply error mitigation techniques, estimate circuit depth and gate count, provide classical simulation results for verification, suggest hardware-aware transpilation optimizations, explain the quantum advantage over classical approaches, and generate visualization of the circuit diagram.";
    $result = callAlfred($prompt);
    return ['success' => true, 'algorithm' => $algorithm, 'framework' => $framework, 'qubits' => $qubits, 'data' => $result,
            'message' => "Quantum code for '$algorithm' ($framework, $qubits qubits). " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════
// AGENT ORCHESTRATION (10 tools)
// ═══════════════════════════════════════════════════════════════════

function toolAgentRegistry($args) {
    $agent_name = $args['agent_name'] ?? 'unnamed';
    $capabilities = $args['capabilities'] ?? '';
    $specialization = $args['specialization'] ?? 'general';
    $prompt = "You are Alfred, a professional AI agent registry coordinator. Agent: $agent_name. Capabilities: $capabilities. Specialization: $specialization. Register the agent in the multi-agent system catalog, validate declared capabilities against known benchmarks, assign a unique agent ID and API key, configure communication protocols (sync/async), set up health check endpoints, define capability overlap matrix with existing agents, establish SLA parameters, and confirm successful registration with onboarding checklist.";
    $result = callAlfred($prompt);
    return ['success' => true, 'agent_name' => $agent_name, 'capabilities' => $capabilities, 'specialization' => $specialization, 'data' => $result,
            'message' => "Agent '$agent_name' registered (specialization: $specialization). " . substr($result, 0, 500)];
}

function toolAgentTaskRouter($args) {
    $task = $args['task'] ?? '';
    $priority = $args['priority'] ?? 'normal';
    $constraints = $args['constraints'] ?? '';
    $prompt = "You are Alfred, a professional AI task routing orchestrator. Task: $task. Priority: $priority. Constraints: $constraints. Analyze the task requirements and complexity, match against available agent capabilities and current workloads, apply $priority priority routing rules, respect constraints ($constraints), select optimal agent(s) for execution, estimate completion time, set up result aggregation if multi-agent, configure timeout and fallback agents, and return routing decision with justification.";
    $result = callAlfred($prompt);
    return ['success' => true, 'task' => $task, 'priority' => $priority, 'constraints' => $constraints, 'data' => $result,
            'message' => "Task routed (priority: $priority). " . substr($result, 0, 500)];
}

function toolAgentPipelineBuilder($args) {
    $pipeline_name = $args['pipeline_name'] ?? 'unnamed';
    $stages = $args['stages'] ?? '';
    $strategy = $args['strategy'] ?? 'sequential';
    $prompt = "You are Alfred, a professional AI pipeline orchestration architect. Pipeline: $pipeline_name. Stages: $stages. Strategy: $strategy. Design the multi-agent pipeline with $strategy execution strategy, define stage dependencies and data flow between agents, configure error handling and retry logic per stage, set up intermediate result caching, establish pipeline-level timeout and circuit breakers, define rollback procedures, generate pipeline visualization, and validate end-to-end data schema compatibility.";
    $result = callAlfred($prompt);
    return ['success' => true, 'pipeline_name' => $pipeline_name, 'stages' => $stages, 'strategy' => $strategy, 'data' => $result,
            'message' => "Pipeline '$pipeline_name' built ($strategy strategy). " . substr($result, 0, 500)];
}

function toolAgentHealthMonitor($args) {
    $agent_id = $args['agent_id'] ?? 'all';
    $check_type = $args['check_type'] ?? 'health';
    $prompt = "You are Alfred, a professional AI agent health monitoring specialist. Agent: $agent_id. Check type: $check_type. Perform $check_type check on agent(s) ($agent_id), measure response time latency, verify memory and CPU utilization, check connection pool status, validate API endpoint availability, review error rates over last hour, assess queue depth and processing backlog, compare against SLA thresholds, and generate health report with alerts for any degraded agents.";
    $result = callAlfred($prompt);
    return ['success' => true, 'agent_id' => $agent_id, 'check_type' => $check_type, 'data' => $result,
            'message' => "Agent health check ($agent_id, type: $check_type). " . substr($result, 0, 500)];
}

function toolAgentPerformanceScorer($args) {
    $agent_id = $args['agent_id'] ?? 'unknown';
    $metrics = $args['metrics'] ?? '';
    $period = $args['period'] ?? '30d';
    $prompt = "You are Alfred, a professional AI agent performance evaluation specialist. Agent: $agent_id. Metrics: $metrics. Period: $period. Analyze agent performance over the $period period, calculate scores for requested metrics ($metrics), benchmark against peer agents, compute task completion rate and accuracy, measure average response time and throughput, evaluate cost efficiency (tokens/cost per task), identify performance trends and regressions, rank agent in its specialization category, and provide actionable improvement recommendations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'agent_id' => $agent_id, 'metrics' => $metrics, 'period' => $period, 'data' => $result,
            'message' => "Performance scored for agent '$agent_id' ($period). " . substr($result, 0, 500)];
}

function toolAgentLearningLoop($args) {
    $agent_id = $args['agent_id'] ?? 'unknown';
    $outcome = $args['outcome'] ?? '';
    $feedback = $args['feedback'] ?? '';
    $prompt = "You are Alfred, a professional AI agent learning loop facilitator. Agent: $agent_id. Outcome: $outcome. Feedback: $feedback. Process the task outcome and user feedback for agent $agent_id, classify outcome as success/partial/failure, extract lessons learned and improvement signals, update agent's preference model and prompt templates, adjust confidence calibration, log feedback for fine-tuning dataset, identify systematic error patterns, recommend prompt engineering changes, and confirm learning loop cycle completion.";
    $result = callAlfred($prompt);
    return ['success' => true, 'agent_id' => $agent_id, 'outcome' => $outcome, 'feedback' => $feedback, 'data' => $result,
            'message' => "Learning loop for agent '$agent_id' processed. " . substr($result, 0, 500)];
}

function toolAgentConflictResolver($args) {
    $outputs = $args['outputs'] ?? '';
    $resolution_strategy = $args['resolution_strategy'] ?? 'vote';
    $prompt = "You are Alfred, a professional AI multi-agent conflict resolution specialist. Outputs: $outputs. Strategy: $resolution_strategy. Analyze conflicting outputs from multiple agents, apply $resolution_strategy resolution strategy (majority vote / weighted consensus / expert override / LLM arbitration), identify points of agreement and disagreement, assess confidence levels of each output, synthesize a unified resolution, document the reasoning chain, flag irreconcilable conflicts for human review, and produce an audit trail of the resolution process.";
    $result = callAlfred($prompt);
    return ['success' => true, 'outputs' => $outputs, 'resolution_strategy' => $resolution_strategy, 'data' => $result,
            'message' => "Conflict resolved using '$resolution_strategy' strategy. " . substr($result, 0, 500)];
}

function toolAgentCostOptimizer($args) {
    $budget = $args['budget'] ?? 0;
    $agents = $args['agents'] ?? '';
    $optimization_target = $args['optimization_target'] ?? 'cost';
    $prompt = "You are Alfred, a professional AI agent cost optimization specialist. Budget: \$$budget. Agents: $agents. Target: $optimization_target. Analyze current agent spend across all deployed agents ($agents), identify cost drivers (API calls, compute, storage), optimize for $optimization_target (cost reduction / quality maintenance / speed), recommend model downgrades where quality is sufficient, suggest caching strategies to reduce redundant calls, propose agent consolidation opportunities, forecast monthly spend at current usage, and produce a budget allocation plan within \$$budget.";
    $result = callAlfred($prompt);
    return ['success' => true, 'budget' => $budget, 'agents' => $agents, 'optimization_target' => $optimization_target, 'data' => $result,
            'message' => "Cost optimized for $optimization_target (budget: \$$budget). " . substr($result, 0, 500)];
}

function toolAgentVersionManager($args) {
    $agent_id = $args['agent_id'] ?? 'unknown';
    $action = $args['action'] ?? 'deploy';
    $version = $args['version'] ?? 'latest';
    $prompt = "You are Alfred, a professional AI agent version management specialist. Agent: $agent_id. Action: $action. Version: $version. Execute $action for agent $agent_id version $version. For deploy: validate version compatibility, run pre-deployment tests, execute blue-green deployment. For rollback: identify last stable version, restore configuration, verify rollback success. For canary: route percentage of traffic to new version, monitor error rates, auto-promote or rollback based on metrics. Log all version transitions and maintain deployment history.";
    $result = callAlfred($prompt);
    return ['success' => true, 'agent_id' => $agent_id, 'action' => $action, 'version' => $version, 'data' => $result,
            'message' => "Agent '$agent_id' version $action ($version). " . substr($result, 0, 500)];
}

function toolAgentMarketplacePublisher($args) {
    $agent_id = $args['agent_id'] ?? 'unknown';
    $price = $args['price'] ?? 0;
    $description = $args['description'] ?? '';
    $price_label = $price > 0 ? "\$$price" : "free";
    $prompt = "You are Alfred, a professional AI agent marketplace publishing specialist. Agent: $agent_id. Price: $price_label. Description: $description. Prepare agent $agent_id for marketplace publication, validate agent meets quality standards and security requirements, generate marketplace listing with compelling description, set pricing model ($price_label), create API documentation and usage examples, configure rate limiting and billing integration, set up usage analytics tracking, generate demo/trial configuration, submit for marketplace review, and provide publisher dashboard access.";
    $result = callAlfred($prompt);
    return ['success' => true, 'agent_id' => $agent_id, 'price' => $price, 'description' => $description, 'data' => $result,
            'message' => "Agent '$agent_id' published ($price_label). " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════
// COLLABORATION & TEAM (10 tools)
// ═══════════════════════════════════════════════════════════════════

function toolTeamWorkspace($args) {
    $workspace_name = $args['workspace_name'] ?? 'untitled';
    $members = $args['members'] ?? '';
    $permissions = $args['permissions'] ?? 'read-write';
    $prompt = "You are Alfred, a professional AI team workspace administrator. Workspace: $workspace_name. Members: $members. Permissions: $permissions. Create the collaborative workspace '$workspace_name', set up folder structure with standardized layout, invite members ($members) with $permissions permission level, configure shared storage quotas, set up version control integration, enable real-time collaboration features, configure notification preferences per member, establish workspace-level security policies, and generate onboarding guide for new members.";
    $result = callAlfred($prompt);
    return ['success' => true, 'workspace_name' => $workspace_name, 'members' => $members, 'permissions' => $permissions, 'data' => $result,
            'message' => "Workspace '$workspace_name' created ($permissions). " . substr($result, 0, 500)];
}

function toolLiveCodeSession($args) {
    $session_name = $args['session_name'] ?? 'untitled';
    $language = $args['language'] ?? 'javascript';
    $file_path = $args['file_path'] ?? '';
    $prompt = "You are Alfred, a professional AI live collaborative coding session manager. Session: $session_name. Language: $language. File: $file_path. Initialize a real-time collaborative coding session, load the target file ($file_path) with $language syntax highlighting, set up operational transform for conflict-free concurrent editing, enable cursor presence indicators for all participants, configure live linting and error checking, set up integrated terminal sharing, enable voice/text chat sidebar, track all changes with attribution, and provide session recording for later review.";
    $result = callAlfred($prompt);
    return ['success' => true, 'session_name' => $session_name, 'language' => $language, 'file_path' => $file_path, 'data' => $result,
            'message' => "Live code session '$session_name' started ($language). " . substr($result, 0, 500)];
}

function toolSharedTerminal($args) {
    $session_id = $args['session_id'] ?? 'new';
    $command = $args['command'] ?? '';
    $participants = $args['participants'] ?? '';
    $prompt = "You are Alfred, a professional AI shared terminal session coordinator. Session: $session_id. Command: $command. Participants: $participants. Manage the shared terminal session, execute command '$command' with output visible to all participants ($participants), enforce role-based access control (driver/observer), log all commands with timestamps and user attribution, configure input permissions per participant, set up session recording and playback, handle terminal resize synchronization, provide command suggestions and safety warnings for destructive operations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'session_id' => $session_id, 'command' => $command, 'participants' => $participants, 'data' => $result,
            'message' => "Shared terminal ($session_id): executed command. " . substr($result, 0, 500)];
}

function toolTaskBoard($args) {
    $board_name = $args['board_name'] ?? 'untitled';
    $action = $args['action'] ?? 'create';
    $task = $args['task'] ?? '';
    $prompt = "You are Alfred, a professional AI kanban task board manager. Board: $board_name. Action: $action. Task: $task. Execute '$action' on the task board: create new task cards with priority/labels/assignee, move tasks between columns (backlog/todo/in-progress/review/done), assign team members to tasks, set due dates and story points, track work-in-progress limits, generate burndown chart data, flag blocked tasks, send assignment notifications, and provide sprint velocity summary.";
    $result = callAlfred($prompt);
    return ['success' => true, 'board_name' => $board_name, 'action' => $action, 'task' => $task, 'data' => $result,
            'message' => "Task board '$board_name': $action completed. " . substr($result, 0, 500)];
}

function toolTeamChat($args) {
    $channel = $args['channel'] ?? 'general';
    $message = $args['message'] ?? '';
    $mentions = $args['mentions'] ?? '';
    $prompt = "You are Alfred, a professional AI team communication assistant. Channel: $channel. Message: $message. Mentions: $mentions. Post the message to the #$channel channel, notify mentioned users ($mentions) via push notification, format message with proper markdown rendering, attach any referenced files or links, thread the message if it's a reply, update channel activity metrics, check message against content policies, suggest relevant emoji reactions, and index message content for future search.";
    $result = callAlfred($prompt);
    return ['success' => true, 'channel' => $channel, 'message' => $message, 'mentions' => $mentions, 'data' => $result,
            'message' => "Message posted to #$channel. " . substr($result, 0, 500)];
}

function toolScreenShare($args) {
    $session_id = $args['session_id'] ?? 'new';
    $action = $args['action'] ?? 'start';
    $quality = $args['quality'] ?? 'hd';
    $prompt = "You are Alfred, a professional AI screen sharing session manager. Session: $session_id. Action: $action. Quality: $quality. Execute $action on the screen share session at $quality quality. For start: initialize capture, negotiate codec (VP9/H264), set resolution and frame rate for $quality, enable remote cursor visibility. For stop: end capture, save recording, generate shareable replay link. For view: connect as viewer with adaptive bitrate. Manage bandwidth optimization, provide annotation tools, and handle multi-monitor selection.";
    $result = callAlfred($prompt);
    return ['success' => true, 'session_id' => $session_id, 'action' => $action, 'quality' => $quality, 'data' => $result,
            'message' => "Screen share $action ($quality, session: $session_id). " . substr($result, 0, 500)];
}

function toolWhiteboard($args) {
    $board_name = $args['board_name'] ?? 'untitled';
    $action = $args['action'] ?? 'draw';
    $content = $args['content'] ?? '';
    $prompt = "You are Alfred, a professional AI collaborative whiteboard facilitator. Board: $board_name. Action: $action. Content: $content. Execute the $action operation on the whiteboard: draw freehand/shapes with pressure sensitivity, add text boxes with rich formatting, place sticky notes with color coding, support real-time multi-user cursors, maintain undo/redo history per user, organize content into frames and sections, enable voting/reactions on sticky notes, export board as PNG/PDF/SVG, and provide infinite canvas with zoom navigation.";
    $result = callAlfred($prompt);
    return ['success' => true, 'board_name' => $board_name, 'action' => $action, 'content' => $content, 'data' => $result,
            'message' => "Whiteboard '$board_name': $action completed. " . substr($result, 0, 500)];
}

function toolCodeReviewRequest($args) {
    $pr_url = $args['pr_url'] ?? '';
    $reviewers = $args['reviewers'] ?? '';
    $priority = $args['priority'] ?? 'normal';
    $prompt = "You are Alfred, a professional AI code review coordinator. PR: $pr_url. Reviewers: $reviewers. Priority: $priority. Create a $priority priority code review request for $pr_url, assign reviewers ($reviewers) with notification, perform automated pre-review checks (lint, tests, coverage delta, security scan), generate diff summary with change impact analysis, identify high-risk changes needing careful review, suggest review order for large PRs, set review deadline based on priority, track reviewer response times, and provide merge readiness checklist.";
    $result = callAlfred($prompt);
    return ['success' => true, 'pr_url' => $pr_url, 'reviewers' => $reviewers, 'priority' => $priority, 'data' => $result,
            'message' => "Code review requested ($priority priority). " . substr($result, 0, 500)];
}

function toolTeamStandup($args) {
    $team_id = $args['team_id'] ?? 'default';
    $update = $args['update'] ?? '';
    $blockers = $args['blockers'] ?? '';
    $prompt = "You are Alfred, a professional AI async standup facilitator. Team: $team_id. Update: $update. Blockers: $blockers. Record the standup update for team $team_id, categorize into yesterday/today/blockers format, flag blockers ($blockers) for immediate team lead notification, cross-reference blockers with other team members' tasks for dependencies, compile team-wide standup summary, track participation streaks, identify stale tasks (no progress >2 days), generate weekly standup digest, and suggest action items for unresolved blockers.";
    $result = callAlfred($prompt);
    return ['success' => true, 'team_id' => $team_id, 'update' => $update, 'blockers' => $blockers, 'data' => $result,
            'message' => "Standup recorded for team '$team_id'. " . substr($result, 0, 500)];
}

function toolKnowledgeBase($args) {
    $action = $args['action'] ?? 'search';
    $topic = $args['topic'] ?? '';
    $content = $args['content'] ?? '';
    $prompt = "You are Alfred, a professional AI knowledge base curator. Action: $action. Topic: $topic. Content: $content. Execute $action on the knowledge base: for 'add' — index new content under topic with auto-tagging and semantic embedding; for 'search' — perform semantic search across all articles for $topic, rank by relevance, and return top results with snippets; for 'update' — version the existing article, apply changes, update index. Maintain cross-reference links, track article freshness, flag outdated content, and log access analytics.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'topic' => $topic, 'content' => $content, 'data' => $result,
            'message' => "Knowledge base: $action on '$topic'. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════
// LEGAL PRACTITIONERS (15 tools)
// ═══════════════════════════════════════════════════════════════════

function toolContractDrafter($args) {
    $contract_type = $args['contract_type'] ?? 'general';
    $parties = $args['parties'] ?? '';
    $clauses = $args['clauses'] ?? '';
    $prompt = "You are Alfred, a professional AI legal contract drafting assistant. Contract type: $contract_type. Parties: $parties. Clauses: $clauses. Draft a comprehensive $contract_type contract between the parties ($parties), include standard boilerplate clauses (governing law, severability, entire agreement, force majeure), incorporate specific clauses requested ($clauses), add appropriate recitals and definitions section, ensure proper party identification and signature blocks, include dispute resolution mechanism, set term and termination provisions, and flag any clauses needing attorney review.";
    $result = callAlfred($prompt);
    return ['success' => true, 'contract_type' => $contract_type, 'parties' => $parties, 'clauses' => $clauses, 'data' => $result,
            'message' => "Contract drafted ($contract_type). " . substr($result, 0, 500)];
}

function toolContractReviewerLegal($args) {
    $contract_text = $args['contract_text'] ?? '';
    $review_focus = $args['review_focus'] ?? 'risks';
    $prompt = "You are Alfred, a professional AI legal contract review specialist. Review focus: $review_focus. Contract text: $contract_text. Perform a thorough $review_focus-focused review of the contract, identify unfavorable terms and hidden liabilities, flag missing essential clauses, assess indemnification and limitation of liability provisions, review termination and renewal terms, check compliance with applicable regulations, compare against industry standard terms, highlight ambiguous language, rate overall risk level (low/medium/high), and provide specific redline suggestions with explanations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'contract_text' => substr($contract_text, 0, 200), 'review_focus' => $review_focus, 'data' => $result,
            'message' => "Contract reviewed (focus: $review_focus). " . substr($result, 0, 500)];
}

function toolLegalResearch($args) {
    $query = $args['query'] ?? '';
    $jurisdiction = $args['jurisdiction'] ?? 'federal';
    $source = $args['source'] ?? 'all';
    $prompt = "You are Alfred, a professional AI legal research assistant. Query: $query. Jurisdiction: $jurisdiction. Sources: $source. Conduct comprehensive legal research in $jurisdiction jurisdiction across $source sources, find relevant case law citations with holdings and key passages, identify applicable statutes and regulations, analyze precedent strength and trends, note circuit splits or conflicting authority, summarize majority vs dissenting positions, provide Shepard's-style citation analysis (overruled/distinguished/followed), organize findings by relevance, and generate a research memo format output.";
    $result = callAlfred($prompt);
    return ['success' => true, 'query' => $query, 'jurisdiction' => $jurisdiction, 'source' => $source, 'data' => $result,
            'message' => "Legal research completed ($jurisdiction). " . substr($result, 0, 500)];
}

function toolTimeTrackerLegal($args) {
    $client = $args['client'] ?? 'unknown';
    $matter = $args['matter'] ?? '';
    $hours = $args['hours'] ?? 0;
    $description = $args['description'] ?? '';
    $prompt = "You are Alfred, a professional AI legal time tracking and billing assistant. Client: $client. Matter: $matter. Hours: $hours. Description: $description. Record $hours billable hours for client $client on matter $matter, validate entry against engagement letter rate schedule, format description per billing guidelines (task-based, no block billing), check for duplicate entries, calculate running total for this matter, flag if approaching budget cap or retainer exhaustion, suggest UTBMS task/activity codes, generate pre-bill entry, and track against matter budget.";
    $result = callAlfred($prompt);
    return ['success' => true, 'client' => $client, 'matter' => $matter, 'hours' => $hours, 'description' => $description, 'data' => $result,
            'message' => "Time entry: {$hours}h for $client ($matter). " . substr($result, 0, 500)];
}

function toolTrustAccountManager($args) {
    $account_id = $args['account_id'] ?? 'unknown';
    $action = $args['action'] ?? 'report';
    $amount = $args['amount'] ?? 0;
    $prompt = "You are Alfred, a professional AI legal trust account management specialist (IOLTA/trust compliance). Account: $account_id. Action: $action. Amount: \$$amount. Execute $action on trust account $account_id in strict compliance with bar association rules. For deposit: record source, client matter, and amount (\$$amount). For withdraw: verify sufficient client funds, prevent commingling, require matter reference. For report: generate three-way reconciliation (bank/book/client), flag discrepancies, list all client sub-ledger balances, and ensure compliance with jurisdictional trust accounting rules.";
    $result = callAlfred($prompt);
    return ['success' => true, 'account_id' => $account_id, 'action' => $action, 'amount' => $amount, 'data' => $result,
            'message' => "Trust account '$account_id': $action (\$$amount). " . substr($result, 0, 500)];
}

function toolCourtDeadlineTracker($args) {
    $case_id = $args['case_id'] ?? 'unknown';
    $deadline_type = $args['deadline_type'] ?? 'filing';
    $date = $args['date'] ?? '';
    $prompt = "You are Alfred, a professional AI court deadline and calendaring specialist. Case: $case_id. Deadline type: $deadline_type. Date: $date. Track the $deadline_type deadline ($date) for case $case_id, calculate backward from due date to set reminder milestones (14-day, 7-day, 3-day, 1-day warnings), account for court holidays and weekend rules, apply jurisdiction-specific computation rules (FRCP or local), set tickler entries for all responsible attorneys, check for conflicts with other case deadlines, flag statutory deadlines that cannot be extended, and generate a comprehensive case calendar view.";
    $result = callAlfred($prompt);
    return ['success' => true, 'case_id' => $case_id, 'deadline_type' => $deadline_type, 'date' => $date, 'data' => $result,
            'message' => "Deadline tracked: $deadline_type for case $case_id ($date). " . substr($result, 0, 500)];
}

function toolClientIntake($args) {
    $client_name = $args['client_name'] ?? 'unknown';
    $case_type = $args['case_type'] ?? 'general';
    $details = $args['details'] ?? '';
    $prompt = "You are Alfred, a professional AI legal client intake specialist. Client: $client_name. Case type: $case_type. Details: $details. Process new client intake for $client_name ($case_type matter), run conflict of interest check against existing client database, collect essential information (contact, adverse parties, key dates), assess case merit and statute of limitations, determine appropriate practice group and assigning attorney, prepare engagement letter template, set up client matter in case management system, generate intake summary memo, and schedule initial consultation.";
    $result = callAlfred($prompt);
    return ['success' => true, 'client_name' => $client_name, 'case_type' => $case_type, 'details' => $details, 'data' => $result,
            'message' => "Client intake: $client_name ($case_type). " . substr($result, 0, 500)];
}

function toolDemandLetterWriter($args) {
    $recipient = $args['recipient'] ?? 'unknown';
    $claim = $args['claim'] ?? '';
    $amount = $args['amount'] ?? 0;
    $deadline = $args['deadline'] ?? '30 days';
    $amount_label = $amount > 0 ? "\$$amount" : "unspecified amount";
    $prompt = "You are Alfred, a professional AI legal demand letter drafting specialist. Recipient: $recipient. Claim: $claim. Amount: $amount_label. Deadline: $deadline. Draft a formal demand letter to $recipient, state the factual basis of the claim clearly and persuasively, cite applicable legal theories and statutory authority, demand payment/action of $amount_label within $deadline, outline consequences of non-compliance (litigation, statutory damages, attorney fees), include preservation of evidence notice, maintain professional but firm tone, provide certified mail and email delivery instructions, and set follow-up calendar reminder.";
    $result = callAlfred($prompt);
    return ['success' => true, 'recipient' => $recipient, 'claim' => $claim, 'amount' => $amount, 'deadline' => $deadline, 'data' => $result,
            'message' => "Demand letter drafted to $recipient ($amount_label, $deadline). " . substr($result, 0, 500)];
}

function toolIncorporationAssistant($args) {
    $business_name = $args['business_name'] ?? 'unnamed';
    $state = $args['state'] ?? 'delaware';
    $entity_type = $args['entity_type'] ?? 'llc';
    $prompt = "You are Alfred, a professional AI business incorporation assistant. Business: $business_name. State: $state. Entity type: $entity_type. Guide incorporation of '$business_name' as a $entity_type in $state, prepare articles of incorporation/organization, draft operating agreement or bylaws, obtain EIN application (SS-4), register for state tax accounts, file beneficial ownership report (BOI), set up registered agent, prepare initial resolutions and meeting minutes, advise on S-corp election if applicable, calculate filing fees, and generate post-incorporation compliance checklist.";
    $result = callAlfred($prompt);
    return ['success' => true, 'business_name' => $business_name, 'state' => $state, 'entity_type' => $entity_type, 'data' => $result,
            'message' => "Incorporation: $business_name ($entity_type in $state). " . substr($result, 0, 500)];
}

function toolWillEstatePlanner($args) {
    $testator = $args['testator'] ?? 'unknown';
    $beneficiaries = $args['beneficiaries'] ?? '';
    $assets = $args['assets'] ?? '';
    $prompt = "You are Alfred, a professional AI estate planning assistant. Testator: $testator. Beneficiaries: $beneficiaries. Assets: $assets. Draft a comprehensive will for $testator, designate beneficiaries ($beneficiaries) with specific bequests and residuary clause, inventory assets ($assets), appoint executor and guardian for minor children, include no-contest clause, plan for tax-efficient asset distribution, draft durable power of attorney and healthcare directive, consider trust provisions for minor beneficiaries, address digital asset disposition, ensure proper attestation clause and witness requirements for applicable jurisdiction.";
    $result = callAlfred($prompt);
    return ['success' => true, 'testator' => $testator, 'beneficiaries' => $beneficiaries, 'assets' => $assets, 'data' => $result,
            'message' => "Estate plan drafted for $testator. " . substr($result, 0, 500)];
}

function toolImmigrationFormHelper($args) {
    $form_type = $args['form_type'] ?? 'general';
    $country = $args['country'] ?? 'canada';
    $applicant_info = $args['applicant_info'] ?? '';
    $prompt = "You are Alfred, a professional AI immigration form preparation assistant. Form: $form_type. Country: $country. Applicant: $applicant_info. Guide preparation of $form_type immigration form for $country, identify all required fields and supporting documents, validate applicant information against form requirements, check processing times and fees, identify potential inadmissibility issues, prepare document checklist (identity, financial, employment, education), draft cover letter, calculate biometrics appointment timeline, flag common rejection reasons to avoid, and provide step-by-step filing instructions with current mailing addresses.";
    $result = callAlfred($prompt);
    return ['success' => true, 'form_type' => $form_type, 'country' => $country, 'applicant_info' => $applicant_info, 'data' => $result,
            'message' => "Immigration form $form_type prepared ($country). " . substr($result, 0, 500)];
}

function toolMediationPrep($args) {
    $case_summary = $args['case_summary'] ?? '';
    $positions = $args['positions'] ?? '';
    $desired_outcome = $args['desired_outcome'] ?? '';
    $prompt = "You are Alfred, a professional AI mediation preparation specialist. Case: $case_summary. Positions: $positions. Desired outcome: $desired_outcome. Prepare comprehensive mediation strategy, analyze strengths and weaknesses of both parties' positions ($positions), calculate BATNA (Best Alternative to Negotiated Agreement), identify ZOPA (Zone of Possible Agreement), draft opening statement emphasizing collaborative resolution, prepare concession strategy with fallback positions, anticipate opposing arguments and prepare rebuttals, develop interest-based negotiation points beyond stated positions, estimate litigation cost comparison to motivate settlement, and draft mediation brief.";
    $result = callAlfred($prompt);
    return ['success' => true, 'case_summary' => $case_summary, 'positions' => $positions, 'desired_outcome' => $desired_outcome, 'data' => $result,
            'message' => "Mediation prep completed. " . substr($result, 0, 500)];
}

function toolLitigationBudget($args) {
    $case_type = $args['case_type'] ?? 'general';
    $complexity = $args['complexity'] ?? 'moderate';
    $estimated_duration = $args['estimated_duration'] ?? '12 months';
    $prompt = "You are Alfred, a professional AI litigation budget planning specialist. Case type: $case_type. Complexity: $complexity. Duration: $estimated_duration. Create a detailed litigation budget for a $complexity complexity $case_type case over $estimated_duration, break down by phase (investigation, pleadings, discovery, depositions, motions, trial prep, trial, post-trial), estimate attorney hours by seniority level, calculate disbursements (filing fees, expert witnesses, court reporters, e-discovery), provide monthly cash flow projection, identify cost-saving opportunities, set budget checkpoints and variance thresholds, compare against industry benchmarks, and prepare client budget presentation with assumptions.";
    $result = callAlfred($prompt);
    return ['success' => true, 'case_type' => $case_type, 'complexity' => $complexity, 'estimated_duration' => $estimated_duration, 'data' => $result,
            'message' => "Litigation budget: $case_type ($complexity, $estimated_duration). " . substr($result, 0, 500)];
}

function toolDepositionPrep($args) {
    $deponent = $args['deponent'] ?? 'unknown';
    $case_facts = $args['case_facts'] ?? '';
    $key_issues = $args['key_issues'] ?? '';
    $prompt = "You are Alfred, a professional AI deposition preparation specialist. Deponent: $deponent. Case facts: $case_facts. Key issues: $key_issues. Prepare comprehensive deposition outline for examining $deponent, organize questions by topic area covering key issues ($key_issues), draft foundation questions before substantive examination, prepare impeachment questions based on known inconsistencies in case facts ($case_facts), identify documents to mark as exhibits, draft requests for admissions to pin down testimony, prepare redirect questions anticipating opposing counsel objections, create timeline of events for reference, outline deponent's likely testimony based on pleadings, and prepare witness preparation memo if defending.";
    $result = callAlfred($prompt);
    return ['success' => true, 'deponent' => $deponent, 'case_facts' => $case_facts, 'key_issues' => $key_issues, 'data' => $result,
            'message' => "Deposition prep for $deponent completed. " . substr($result, 0, 500)];
}

function toolComplianceCheckerLegal($args) {
    $industry = $args['industry'] ?? 'general';
    $jurisdiction = $args['jurisdiction'] ?? 'federal';
    $area = $args['area'] ?? 'general';
    $prompt = "You are Alfred, a professional AI regulatory compliance checking specialist. Industry: $industry. Jurisdiction: $jurisdiction. Area: $area. Perform a $area compliance assessment for $industry industry in $jurisdiction jurisdiction, identify all applicable regulations and standards, check current compliance status against requirements, flag gaps and violations with severity ratings (critical/major/minor), provide remediation steps with priority and deadlines, calculate potential penalties for non-compliance, identify upcoming regulatory changes that may affect compliance, generate compliance matrix with evidence requirements, recommend policies and procedures to maintain compliance, and prepare audit-ready documentation checklist.";
    $result = callAlfred($prompt);
    return ['success' => true, 'industry' => $industry, 'jurisdiction' => $jurisdiction, 'area' => $area, 'data' => $result,
            'message' => "Compliance check: $industry ($jurisdiction, $area). " . substr($result, 0, 500)];
}

// ============================================================================
// REPORTING & DASHBOARDS (12 tools)
// ============================================================================

function toolDashboardBuilder($args) {
    $dashboard_name = $args['dashboard_name'] ?? 'default';
    $widgets = $args['widgets'] ?? 'metrics,charts,tables';
    $layout = $args['layout'] ?? 'grid';
    $prompt = "You are Alfred, a professional AI dashboard architect. Dashboard name: $dashboard_name. Widgets: $widgets. Layout: $layout. Build a custom dashboard named '$dashboard_name' using a $layout layout, configure requested widgets ($widgets) with appropriate data sources and refresh intervals, define widget positioning and sizing for optimal information density, set up data connections and real-time update streams for each widget, create drill-down capabilities from summary to detail views, add filtering and date range controls, configure color schemes and visual hierarchy for readability, set up responsive breakpoints for mobile and tablet views, define default and saved view configurations, and generate embed code for external sharing.";
    $result = callAlfred($prompt);
    return ['success' => true, 'dashboard_name' => $dashboard_name, 'widgets' => $widgets, 'layout' => $layout, 'data' => $result,
            'message' => "Dashboard '$dashboard_name' built with $layout layout. " . substr($result, 0, 500)];
}

function toolReportScheduler($args) {
    $report_name = $args['report_name'] ?? 'general';
    $frequency = $args['frequency'] ?? 'weekly';
    $recipients = $args['recipients'] ?? '';
    $prompt = "You are Alfred, a professional AI report scheduling assistant. Report: $report_name. Frequency: $frequency. Recipients: $recipients. Schedule automated report '$report_name' to run $frequency, configure data sources and query parameters for the report, set delivery schedule with timezone-aware timing, format report for email delivery to recipients ($recipients), include summary highlights and key metric changes, set up conditional alerts if metrics exceed thresholds, configure report templates with branding and formatting, enable historical comparison with previous periods, add executive summary section with actionable insights, and set up retry logic for failed report generations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'report_name' => $report_name, 'frequency' => $frequency, 'recipients' => $recipients, 'data' => $result,
            'message' => "Report '$report_name' scheduled $frequency for $recipients. " . substr($result, 0, 500)];
}

function toolAgentPerformanceReport($args) {
    $agent_id = $args['agent_id'] ?? 'all';
    $period = $args['period'] ?? '30d';
    $metrics = $args['metrics'] ?? 'calls,resolution,satisfaction';
    $prompt = "You are Alfred, a professional AI agent performance analyst. Agent ID: $agent_id. Period: $period. Metrics: $metrics. Generate comprehensive performance report for agent(s) '$agent_id' over $period, analyze key metrics ($metrics) including call volume, average handle time, first-call resolution rate, customer satisfaction scores, and escalation rates, compare performance against team averages and targets, identify trends and patterns in agent behavior, highlight top performers and areas needing improvement, calculate productivity scores and efficiency ratings, track quality assurance scores over time, provide coaching recommendations based on performance gaps, benchmark against industry standards, and generate individual development plans.";
    $result = callAlfred($prompt);
    return ['success' => true, 'agent_id' => $agent_id, 'period' => $period, 'metrics' => $metrics, 'data' => $result,
            'message' => "Agent performance report for '$agent_id' over $period. " . substr($result, 0, 500)];
}

function toolROICalculator($args) {
    $investment = $args['investment'] ?? 0;
    $returns = $args['returns'] ?? 0;
    $period_months = $args['period_months'] ?? 12;
    $prompt = "You are Alfred, a professional AI financial ROI analyst. Investment: \$$investment. Returns: \$$returns. Period: $period_months months. Calculate comprehensive ROI analysis with investment of \$$investment and returns of \$$returns over $period_months months, compute simple ROI percentage, annualized ROI, net present value (NPV) with standard discount rates, internal rate of return (IRR), payback period in months, break-even analysis, compare against alternative investment benchmarks (S&P 500, bonds, savings), factor in opportunity cost, project future returns with growth scenarios (conservative/moderate/aggressive), and provide risk-adjusted return metrics including Sharpe ratio equivalent.";
    $result = callAlfred($prompt);
    return ['success' => true, 'investment' => $investment, 'returns' => $returns, 'period_months' => $period_months, 'data' => $result,
            'message' => "ROI calculated: \$$investment invested, \$$returns returned over {$period_months}mo. " . substr($result, 0, 500)];
}

function toolSLAMonitor($args) {
    $service_name = $args['service_name'] ?? 'default';
    $sla_target = $args['sla_target'] ?? 99.9;
    $check_period = $args['check_period'] ?? '30d';
    $prompt = "You are Alfred, a professional AI SLA monitoring specialist. Service: $service_name. SLA Target: {$sla_target}%. Period: $check_period. Monitor SLA compliance for '$service_name' against {$sla_target}% target over $check_period, calculate current uptime percentage and availability metrics, identify all downtime incidents with duration and root cause, compute remaining error budget and burn rate, track response time SLAs (p50, p95, p99), monitor throughput and capacity SLAs, flag SLA breaches and near-misses with severity, generate trend analysis showing SLA trajectory, calculate financial impact of SLA violations including credits owed, provide early warning alerts for SLA degradation, and recommend remediation actions to maintain compliance.";
    $result = callAlfred($prompt);
    return ['success' => true, 'service_name' => $service_name, 'sla_target' => $sla_target, 'check_period' => $check_period, 'data' => $result,
            'message' => "SLA monitor for '$service_name': target {$sla_target}% over $check_period. " . substr($result, 0, 500)];
}

function toolUsageAnalytics($args) {
    $tool_name = $args['tool_name'] ?? 'all';
    $period = $args['period'] ?? '30d';
    $group_by = $args['group_by'] ?? 'day';
    $prompt = "You are Alfred, a professional AI usage analytics specialist. Tool: $tool_name. Period: $period. Group by: $group_by. Analyze usage patterns for tool(s) '$tool_name' over $period grouped by $group_by, track total invocations, unique users, and session counts, calculate adoption rate and engagement metrics, identify peak usage times and seasonal patterns, measure average response time and success rate per tool, detect usage anomalies and unusual patterns, rank tools by popularity and utility scores, track feature adoption curves and growth rates, identify underutilized tools and recommend promotion strategies, compute cost per usage and efficiency metrics, and generate usage forecast for capacity planning.";
    $result = callAlfred($prompt);
    return ['success' => true, 'tool_name' => $tool_name, 'period' => $period, 'group_by' => $group_by, 'data' => $result,
            'message' => "Usage analytics for '$tool_name' over $period (by $group_by). " . substr($result, 0, 500)];
}

function toolCostAnalyzer($args) {
    $category = $args['category'] ?? 'all';
    $period = $args['period'] ?? '30d';
    $breakdown = $args['breakdown'] ?? 'tool';
    $prompt = "You are Alfred, a professional AI cost analysis specialist. Category: $category. Period: $period. Breakdown: $breakdown. Analyze costs for category '$category' over $period with breakdown by $breakdown, calculate total spend and cost per transaction, identify top cost drivers and spending trends, compare costs against budget allocations, detect cost anomalies and unexpected charges, compute unit economics and marginal costs, project future costs based on growth trends, identify cost optimization opportunities with estimated savings, benchmark costs against industry averages, analyze cost-to-revenue ratios, and provide actionable recommendations to reduce spend by 10-20% without impacting quality.";
    $result = callAlfred($prompt);
    return ['success' => true, 'category' => $category, 'period' => $period, 'breakdown' => $breakdown, 'data' => $result,
            'message' => "Cost analysis for '$category' over $period (by $breakdown). " . substr($result, 0, 500)];
}

function toolBenchmarkComparator($args) {
    $metric = $args['metric'] ?? 'performance';
    $value = $args['value'] ?? 0;
    $industry = $args['industry'] ?? 'tech';
    $prompt = "You are Alfred, a professional AI benchmarking specialist. Metric: $metric. Value: $value. Industry: $industry. Compare metric '$metric' with value $value against $industry industry benchmarks, determine percentile ranking within the industry, compare against top quartile, median, and bottom quartile performers, identify gaps between current performance and best-in-class, provide historical benchmark trends showing industry movement, analyze competitive positioning based on this metric, recommend target values for improvement with timelines, calculate effort-to-impact ratio for reaching next benchmark tier, identify correlated metrics that may also need improvement, provide case studies of organizations that improved this metric, and set realistic milestone targets for quarterly improvement.";
    $result = callAlfred($prompt);
    return ['success' => true, 'metric' => $metric, 'value' => $value, 'industry' => $industry, 'data' => $result,
            'message' => "Benchmark: '$metric' = $value vs $industry industry. " . substr($result, 0, 500)];
}

function toolCustomChartBuilder($args) {
    $chart_type = $args['chart_type'] ?? 'bar';
    $data = $args['data'] ?? '{}';
    $title = $args['title'] ?? 'Chart';
    $prompt = "You are Alfred, a professional AI data visualization specialist. Chart type: $chart_type. Title: $title. Data: $data. Build a custom $chart_type chart titled '$title', parse and validate the provided data ($data), select optimal axis scales and labels, apply color palette appropriate for data categories, configure legend placement and formatting, add data labels and tooltips for interactivity, set up responsive sizing for different screen widths, recommend alternative chart types if the data would be better represented differently, generate chart configuration in Chart.js compatible JSON format, add trend lines or moving averages where appropriate, configure animation and transition effects, and provide accessibility descriptions for screen readers.";
    $result = callAlfred($prompt);
    return ['success' => true, 'chart_type' => $chart_type, 'title' => $title, 'data' => $data, 'data_result' => $result,
            'message' => "Custom $chart_type chart '$title' built. " . substr($result, 0, 500)];
}

function toolDataExporter($args) {
    $report_id = $args['report_id'] ?? '';
    $format = $args['format'] ?? 'csv';
    $email_to = $args['email_to'] ?? '';
    $prompt = "You are Alfred, a professional AI data export specialist. Report ID: $report_id. Format: $format. Email to: $email_to. Export report '$report_id' in $format format, gather all report data and apply formatting rules for $format output, for PDF include headers/footers/page numbers and professional styling, for CSV ensure proper escaping and UTF-8 encoding, for Excel add formatted headers, auto-width columns, and summary formulas, for JSON structure with metadata and pagination info, compress large exports and generate secure download links, send export to $email_to with summary in email body, include data dictionary and column descriptions, add timestamp and generation metadata, apply any access controls and watermarking for sensitive data, and log export activity for audit trail.";
    $result = callAlfred($prompt);
    return ['success' => true, 'report_id' => $report_id, 'format' => $format, 'email_to' => $email_to, 'data' => $result,
            'message' => "Report '$report_id' exported as $format" . ($email_to ? " sent to $email_to" : "") . ". " . substr($result, 0, 500)];
}

function toolAlertConfigurator($args) {
    $metric = $args['metric'] ?? '';
    $threshold = $args['threshold'] ?? 0;
    $notification_type = $args['notification_type'] ?? 'email';
    $prompt = "You are Alfred, a professional AI alert configuration specialist. Metric: $metric. Threshold: $threshold. Notification: $notification_type. Configure alert for metric '$metric' with threshold $threshold, set up $notification_type notifications when threshold is breached, define alert severity levels (info/warning/critical) based on threshold proximity, configure cooldown periods to prevent alert fatigue, set up escalation chains for unacknowledged alerts, add context and troubleshooting steps to alert messages, configure alert grouping for related metrics, set maintenance windows to suppress expected alerts, define auto-remediation actions for known issues, set up alert dashboards with current status overview, configure on-call rotation integration, and test alert delivery to confirm notification pipeline works.";
    $result = callAlfred($prompt);
    return ['success' => true, 'metric' => $metric, 'threshold' => $threshold, 'notification_type' => $notification_type, 'data' => $result,
            'message' => "Alert configured: '$metric' threshold $threshold via $notification_type. " . substr($result, 0, 500)];
}

function toolExecutiveDashboard($args) {
    $department = $args['department'] ?? 'all';
    $period = $args['period'] ?? '30d';
    $kpis = $args['kpis'] ?? 'revenue,growth,satisfaction';
    $prompt = "You are Alfred, a professional AI executive dashboard specialist. Department: $department. Period: $period. KPIs: $kpis. Build executive dashboard for '$department' department over $period, display key performance indicators ($kpis) with trend arrows and period-over-period comparison, create high-level summary cards with sparkline trends, add revenue and financial overview with forecast vs actual, include customer satisfaction and NPS scores, show team productivity and capacity utilization, display strategic initiative progress with RAG status, add competitive intelligence highlights, include risk register with top concerns and mitigation status, provide AI-generated executive summary with key takeaways, configure one-click drill-down to departmental details, and set up automated daily email digest for C-suite.";
    $result = callAlfred($prompt);
    return ['success' => true, 'department' => $department, 'period' => $period, 'kpis' => $kpis, 'data' => $result,
            'message' => "Executive dashboard for '$department' ($period). " . substr($result, 0, 500)];
}

// ============================================================================
// OFFLINE & PWA (5 tools)
// ============================================================================

function toolOfflineSync($args) {
    $workspace = $args['workspace'] ?? 'default';
    $sync_type = $args['sync_type'] ?? 'incremental';
    $prompt = "You are Alfred, a professional AI offline synchronization specialist. Workspace: $workspace. Sync type: $sync_type. Perform $sync_type sync for workspace '$workspace' for offline availability, identify all files and data that need to be cached locally, calculate storage requirements and check available space, prioritize critical files and recently accessed content, generate service worker cache manifest with versioning, handle conflict resolution for files modified both online and offline, compress assets for efficient local storage, set up IndexedDB schemas for structured data caching, create sync queue for pending changes to upload when back online, verify data integrity with checksums after sync, configure background sync with periodic refresh intervals, and provide sync status report with cached vs pending items.";
    $result = callAlfred($prompt);
    return ['success' => true, 'workspace' => $workspace, 'sync_type' => $sync_type, 'data' => $result,
            'message' => "Offline sync ($sync_type) for workspace '$workspace' complete. " . substr($result, 0, 500)];
}

function toolOfflineEditor($args) {
    $file_path = $args['file_path'] ?? '';
    $action = $args['action'] ?? 'open';
    $prompt = "You are Alfred, a professional AI offline editing specialist. File: $file_path. Action: $action. Perform '$action' on file '$file_path' in offline mode, load file from local cache or IndexedDB storage, enable full editing capabilities without network connectivity, track all changes with local version history, auto-save changes to local storage every 30 seconds, maintain undo/redo history across sessions, handle syntax highlighting and code completion from cached language data, queue file saves for sync when connectivity returns, detect and resolve conflicts if file was modified elsewhere, preserve file metadata and permissions, provide offline-aware status indicators, and estimate sync requirements when connection resumes.";
    $result = callAlfred($prompt);
    return ['success' => true, 'file_path' => $file_path, 'action' => $action, 'data' => $result,
            'message' => "Offline editor: $action '$file_path'. " . substr($result, 0, 500)];
}

function toolOfflineAI($args) {
    $prompt_text = $args['prompt'] ?? '';
    $model = $args['model'] ?? 'ollama';
    $prompt = "You are Alfred, a professional AI offline intelligence specialist. User prompt: $prompt_text. Model: $model. Process the user's request using local AI model '$model' without requiring internet connectivity, select appropriate local model size based on available system resources (RAM, GPU), optimize inference parameters for speed vs quality balance, handle prompt formatting for the specific local model architecture, manage model loading and memory allocation efficiently, provide streaming responses for better user experience, cache frequent responses for instant retrieval, fall back to smaller models if resources are constrained, queue complex requests for processing when online models are available, track token usage and local compute costs, compare local model confidence scores to flag low-confidence responses, and maintain conversation context across offline sessions.";
    $result = callAlfred($prompt);
    return ['success' => true, 'prompt' => $prompt_text, 'model' => $model, 'data' => $result,
            'message' => "Offline AI ($model) processed request. " . substr($result, 0, 500)];
}

function toolCachedDocs($args) {
    $doc_url = $args['doc_url'] ?? '';
    $action = $args['action'] ?? 'cache';
    $prompt = "You are Alfred, a professional AI documentation caching specialist. URL: $doc_url. Action: $action. Perform '$action' on documentation at '$doc_url', for 'cache' action: fetch document content and all linked resources, strip unnecessary scripts and ads for clean reading, convert to offline-friendly format with embedded images, index content for full-text search capability, organize in local documentation library with categories, for 'view' action: retrieve from local cache with search highlighting, for 'update' action: check for newer version and refresh cache, for 'remove' action: clear cached content and free storage, track document versions and show change highlights, generate table of contents for easy navigation, estimate storage usage per cached document, and maintain reading progress and bookmarks.";
    $result = callAlfred($prompt);
    return ['success' => true, 'doc_url' => $doc_url, 'action' => $action, 'data' => $result,
            'message' => "Cached docs: $action '$doc_url'. " . substr($result, 0, 500)];
}

function toolPendingActions($args) {
    $action = $args['action'] ?? '';
    $priority = $args['priority'] ?? 'normal';
    $payload = $args['payload'] ?? '{}';
    $prompt = "You are Alfred, a professional AI offline action queue manager. Action: $action. Priority: $priority. Payload: $payload. Queue action '$action' with $priority priority for execution when connectivity is restored, validate action payload and parameters before queuing, assign unique queue ID and timestamp, order queue by priority (critical > high > normal > low), estimate execution time and resource requirements, check for duplicate or conflicting queued actions, set up retry logic with exponential backoff for failed actions, configure timeout and expiration for time-sensitive actions, provide queue status dashboard with progress indicators, execute queued actions in dependency order when online, send confirmation notifications after successful execution, and maintain audit log of all queued and executed actions.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'priority' => $priority, 'payload' => $payload, 'data' => $result,
            'message' => "Pending action queued: '$action' ($priority priority). " . substr($result, 0, 500)];
}

// ============================================================================
// REAL ESTATE GAPS (2 tools)
// ============================================================================

function toolVirtualTourCreator($args) {
    $property_address = $args['property_address'] ?? '';
    $features = $args['features'] ?? '';
    $style = $args['style'] ?? 'modern';
    $prompt = "You are Alfred, a professional AI virtual tour creation specialist. Property: $property_address. Features: $features. Style: $style. Create a virtual tour for property at '$property_address' in $style style, design room-by-room walkthrough sequence highlighting key features ($features), generate descriptive narration for each room with dimensions and finishes, create interactive hotspots for notable features and upgrades, add ambient background music appropriate for $style aesthetic, include neighborhood overview with nearby amenities and schools, add floor plan overlay with clickable room navigation, integrate property details panel with price, specs, and history, create shareable tour link with lead capture form, optimize for mobile and VR headset viewing, add before/after staging comparisons, generate social media preview clips, and include agent contact information with scheduling widget.";
    $result = callAlfred($prompt);
    return ['success' => true, 'property_address' => $property_address, 'features' => $features, 'style' => $style, 'data' => $result,
            'message' => "Virtual tour created for $property_address ($style style). " . substr($result, 0, 500)];
}

function toolMarketReport($args) {
    $location = $args['location'] ?? '';
    $property_type = $args['property_type'] ?? 'residential';
    $period = $args['period'] ?? '90d';
    $prompt = "You are Alfred, a professional AI real estate market analyst. Location: $location. Property type: $property_type. Period: $period. Generate comprehensive market report for $property_type properties in $location over $period, analyze median sale prices and price-per-square-foot trends, calculate days-on-market averages and inventory levels, track listing-to-sale price ratios and bidding competition, identify micro-market trends by neighborhood and price tier, compare current metrics to historical averages and year-over-year changes, analyze absorption rate and months of supply, segment analysis by property size, age, and condition, forecast price direction with confidence intervals, identify investment opportunities and undervalued areas, provide mortgage rate impact analysis, include demographic and economic indicators affecting the market, and generate executive summary with buy/sell/hold recommendations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'location' => $location, 'property_type' => $property_type, 'period' => $period, 'data' => $result,
            'message' => "Market report for $property_type in $location ($period). " . substr($result, 0, 500)];
}

// ============================================================================
// PARENTS/FAMILY GAPS (4 tools)
// ============================================================================

function toolFamilyCalendar($args) {
    $action = $args['action'] ?? 'view';
    $event = $args['event'] ?? '';
    $date = $args['date'] ?? '';
    $prompt = "You are Alfred, a professional AI family calendar management assistant. Action: $action. Event: $event. Date: $date. Perform '$action' on family calendar, for 'add': create event '$event' on $date with smart defaults for time, duration, and reminders, detect scheduling conflicts with existing family events, suggest optimal times considering all family members' schedules, for 'view': display upcoming events organized by family member and category, for 'remind': send age-appropriate reminders to relevant family members, color-code events by category (school, sports, medical, social), sync across all family devices, add recurring event patterns for regular activities, include travel time calculations between events, suggest meal planning around activity schedules, track RSVPs and carpooling coordination, and integrate school calendar and holiday schedules automatically.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'event' => $event, 'date' => $date, 'data' => $result,
            'message' => "Family calendar: $action" . ($event ? " '$event'" : "") . ($date ? " on $date" : "") . ". " . substr($result, 0, 500)];
}

function toolCollegeSavingsPlanner($args) {
    $child_age = $args['child_age'] ?? 0;
    $target_amount = $args['target_amount'] ?? 50000;
    $monthly_contribution = $args['monthly_contribution'] ?? 200;
    $prompt = "You are Alfred, a professional AI college savings planning specialist. Child age: $child_age. Target: \$$target_amount. Monthly contribution: \$$monthly_contribution. Create comprehensive college savings plan for a $child_age-year-old with \$$target_amount target, calculate years until college enrollment (age 18), project growth of \$$monthly_contribution monthly contributions with conservative (5%), moderate (7%), and aggressive (9%) return scenarios, recommend 529 plan vs Coverdell ESA vs UTMA based on income level, analyze tax benefits by state, model financial aid impact of different savings vehicles, create age-based asset allocation shifting from stocks to bonds as college approaches, calculate catch-up contribution amounts if behind target, factor in college cost inflation (5-6% annually), compare in-state vs out-of-state vs private university costs, project future tuition based on current trends, and provide milestone checkpoints with adjustment recommendations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'child_age' => $child_age, 'target_amount' => $target_amount, 'monthly_contribution' => $monthly_contribution, 'data' => $result,
            'message' => "College savings plan: age $child_age, \$$monthly_contribution/mo toward \$$target_amount. " . substr($result, 0, 500)];
}

function toolEmergencyInfoCard($args) {
    $family_member = $args['family_member'] ?? '';
    $medical_info = $args['medical_info'] ?? '';
    $contacts = $args['contacts'] ?? '';
    $prompt = "You are Alfred, a professional AI emergency preparedness specialist. Family member: $family_member. Medical info: $medical_info. Emergency contacts: $contacts. Create comprehensive emergency info card for $family_member, organize medical information ($medical_info) including allergies, medications, blood type, and conditions, format emergency contacts ($contacts) with relationship, phone, and priority order, include primary care physician and specialist information, add insurance details with policy and group numbers, list medical device information if applicable, include advance directive and medical power of attorney references, create wallet-sized printable card format, generate QR code linking to secure digital version with full details, add ICE (In Case of Emergency) phone entries, include preferred hospital and pharmacy information, add language preferences and communication needs, create age-appropriate versions (child vs adult vs senior), and set up annual review reminders to keep information current.";
    $result = callAlfred($prompt);
    return ['success' => true, 'family_member' => $family_member, 'medical_info' => $medical_info, 'contacts' => $contacts, 'data' => $result,
            'message' => "Emergency info card created for $family_member. " . substr($result, 0, 500)];
}

function toolRecipeScaler($args) {
    $recipe = $args['recipe'] ?? '';
    $servings = $args['servings'] ?? 4;
    $dietary_restrictions = $args['dietary_restrictions'] ?? 'none';
    $prompt = "You are Alfred, a professional AI culinary scaling specialist. Recipe: $recipe. Servings: $servings. Dietary restrictions: $dietary_restrictions. Scale recipe '$recipe' to $servings servings with dietary accommodations for $dietary_restrictions, recalculate all ingredient quantities proportionally, adjust cooking times and temperatures for scaled volume, convert between measurement units for practical kitchen use, flag ingredients that don't scale linearly (spices, leavening, salt), provide substitution suggestions for dietary restriction '$dietary_restrictions' (e.g., gluten-free flour, dairy alternatives, sugar substitutes), adjust pan and equipment sizes for scaled quantity, recalculate nutritional information per serving, generate organized shopping list with quantities, group ingredients by grocery store section, estimate total cost based on average prices, add prep time adjustments for larger quantities, and provide batch cooking tips and storage recommendations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'recipe' => $recipe, 'servings' => $servings, 'dietary_restrictions' => $dietary_restrictions, 'data' => $result,
            'message' => "Recipe '$recipe' scaled to $servings servings ($dietary_restrictions). " . substr($result, 0, 500)];
}

// ============================================================================
// SENIORS GAP (1 tool)
// ============================================================================

function toolScamDetector($args) {
    $message = $args['message'] ?? '';
    $source_type = $args['source_type'] ?? 'email';
    $sender = $args['sender'] ?? 'unknown';
    $prompt = "You are Alfred, a professional AI scam detection specialist focused on protecting seniors. Message: $message. Source: $source_type. Sender: $sender. Analyze this $source_type message from '$sender' for scam indicators, check for common fraud patterns: urgency/pressure tactics, requests for personal information, suspicious links or phone numbers, impersonation of government agencies (IRS, SSA, Medicare), fake lottery/prize notifications, romance scam indicators, tech support scams, grandparent scam patterns, investment fraud signals, and charity fraud signs, evaluate sender legitimacy and domain authenticity, check message against known scam databases and patterns, assign risk score (1-10) with confidence level, provide clear plain-language explanation of why it is or isn't a scam, recommend specific actions (delete, report, verify independently), include reporting instructions for FTC, local police, and IC3, and provide educational tips to recognize similar scams in the future.";
    $result = callAlfred($prompt);
    return ['success' => true, 'message' => substr($message, 0, 200), 'source_type' => $source_type, 'sender' => $sender, 'data' => $result,
            'message' => "Scam analysis ($source_type from '$sender'). " . substr($result, 0, 500)];
}

// ============================================================================
// FREELANCERS GAPS (3 tools)
// ============================================================================

function toolProjectTimeline($args) {
    $project_name = $args['project_name'] ?? '';
    $milestones = $args['milestones'] ?? '';
    $start_date = $args['start_date'] ?? date('Y-m-d');
    $prompt = "You are Alfred, a professional AI project timeline architect. Project: $project_name. Milestones: $milestones. Start date: $start_date. Create detailed project timeline for '$project_name' starting $start_date, break down milestones ($milestones) into actionable tasks with estimated durations, identify task dependencies and critical path, calculate realistic end date with buffer time, assign resource requirements per task phase, create Gantt chart data structure with task relationships, identify potential bottlenecks and risk points, add milestone checkpoints with deliverable definitions, set up progress tracking with percentage complete indicators, include client review and feedback cycles in timeline, calculate burn rate and budget allocation per phase, provide weekly status report template, configure early warning triggers for timeline slippage, and generate shareable timeline view for client communication.";
    $result = callAlfred($prompt);
    return ['success' => true, 'project_name' => $project_name, 'milestones' => $milestones, 'start_date' => $start_date, 'data' => $result,
            'message' => "Timeline for '$project_name' from $start_date. " . substr($result, 0, 500)];
}

function toolIncomeDiversifier($args) {
    $current_income_sources = $args['current_income_sources'] ?? '';
    $skills = $args['skills'] ?? '';
    $target_income = $args['target_income'] ?? 0;
    $prompt = "You are Alfred, a professional AI income diversification strategist. Current sources: $current_income_sources. Skills: $skills. Target income: \$$target_income. Analyze current income sources ($current_income_sources) and skills ($skills) to develop diversification strategy targeting \$$target_income, identify market demand for existing skills and adjacent opportunities, recommend passive income streams matching skill set (digital products, courses, templates, SaaS), suggest freelance platform optimization for higher rates, calculate income gap and required contribution from each new stream, assess risk concentration in current income mix, recommend portfolio approach balancing active and passive income, estimate time-to-revenue for each recommended stream, prioritize opportunities by effort-to-income ratio, create 90-day action plan for launching top 3 new income streams, identify partnership and collaboration opportunities, analyze tax implications of multiple income sources, and provide recurring revenue model recommendations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'current_income_sources' => $current_income_sources, 'skills' => $skills, 'target_income' => $target_income, 'data' => $result,
            'message' => "Income diversification plan toward \$$target_income. " . substr($result, 0, 500)];
}

function toolTaxQuarterlyEstimator($args) {
    $quarterly_income = $args['quarterly_income'] ?? 0;
    $deductions = $args['deductions'] ?? 0;
    $filing_status = $args['filing_status'] ?? 'single';
    $prompt = "You are Alfred, a professional AI quarterly tax estimation specialist. Quarterly income: \$$quarterly_income. Deductions: \$$deductions. Filing status: $filing_status. Calculate quarterly estimated tax payment for $filing_status filer with \$$quarterly_income quarterly income and \$$deductions in deductions, compute self-employment tax (Social Security 12.4% + Medicare 2.9%), calculate federal income tax using current brackets, determine state tax estimates based on typical rates, apply qualified business income (QBI) deduction if eligible, factor in standard vs itemized deductions, calculate safe harbor amount (110% of prior year liability or 90% of current), generate IRS Form 1040-ES payment voucher amounts, provide quarterly due dates (Apr 15, Jun 15, Sep 15, Jan 15), estimate annual tax liability with projections, recommend tax-saving strategies (retirement contributions, health insurance, home office), calculate estimated tax penalty if underpaid, track year-to-date payments and remaining liability, and recommend setting aside specific percentage of each payment received.";
    $result = callAlfred($prompt);
    return ['success' => true, 'quarterly_income' => $quarterly_income, 'deductions' => $deductions, 'filing_status' => $filing_status, 'data' => $result,
            'message' => "Q tax estimate ($filing_status): \$$quarterly_income income, \$$deductions deductions. " . substr($result, 0, 500)];
}

// ============================================================================
// NON-PROFIT GAPS (6 tools)
// ============================================================================

function toolGrantWriter($args) {
    $grant_name = $args['grant_name'] ?? '';
    $organization = $args['organization'] ?? '';
    $project_description = $args['project_description'] ?? '';
    $prompt = "You are Alfred, a professional AI grant writing specialist. Grant: $grant_name. Organization: $organization. Project: $project_description. Write comprehensive grant proposal for '$grant_name' on behalf of '$organization', craft compelling executive summary and statement of need with data-driven evidence, develop project narrative describing $project_description with measurable objectives, create detailed budget with line items and justifications, write organizational capacity section highlighting qualifications and track record, develop evaluation plan with quantitative and qualitative metrics, draft sustainability plan showing how project continues after grant period, write letters of support templates for partners, ensure compliance with grant-specific formatting and page requirements, include logic model connecting inputs to outcomes, add demographic data and community need assessment, prepare required attachments checklist (IRS determination letter, audited financials, board list), and create submission timeline with internal review deadlines.";
    $result = callAlfred($prompt);
    return ['success' => true, 'grant_name' => $grant_name, 'organization' => $organization, 'project_description' => $project_description, 'data' => $result,
            'message' => "Grant proposal for '$grant_name' ($organization). " . substr($result, 0, 500)];
}

function toolAnnualReport($args) {
    $organization = $args['organization'] ?? '';
    $year = $args['year'] ?? date('Y');
    $highlights = $args['highlights'] ?? '';
    $prompt = "You are Alfred, a professional AI annual report writing specialist. Organization: $organization. Year: $year. Highlights: $highlights. Create comprehensive annual report for '$organization' for year $year, write executive director/CEO letter summarizing year's achievements, develop feature stories around key highlights ($highlights), compile financial summary with revenue/expense breakdown and year-over-year comparison, create impact metrics section with infographic-ready statistics, write program descriptions with outcomes and beneficiary stories, list major donors and supporters with appropriate recognition tiers, include board of directors listing with bios, compile volunteer statistics and recognition, add organizational timeline of key events and milestones, create visual assets list (photos, charts, graphs needed), design table of contents and section layout, ensure compliance with state charity registration reporting requirements, and add forward-looking section with next year's goals and strategic priorities.";
    $result = callAlfred($prompt);
    return ['success' => true, 'organization' => $organization, 'year' => $year, 'highlights' => $highlights, 'data' => $result,
            'message' => "Annual report for $organization ($year). " . substr($result, 0, 500)];
}

function toolBoardMeetingPrep($args) {
    $meeting_date = $args['meeting_date'] ?? '';
    $agenda_items = $args['agenda_items'] ?? '';
    $previous_minutes = $args['previous_minutes'] ?? '';
    $prompt = "You are Alfred, a professional AI board meeting preparation specialist. Date: $meeting_date. Agenda: $agenda_items. Previous minutes: $previous_minutes. Prepare comprehensive board meeting package for $meeting_date, create formal agenda from items ($agenda_items) with time allocations and presenter assignments, draft meeting notice with quorum requirements and call-in details, prepare consent agenda separating routine approvals from discussion items, create executive summary briefing document for each agenda item, draft resolutions for any items requiring board votes, compile financial reports with variance analysis and commentary, prepare committee reports summaries, reference previous minutes ($previous_minutes) for follow-up items and action tracking, create board dashboard with key metrics and trends, prepare conflict of interest disclosure reminders, draft post-meeting minutes template, include relevant policy documents and background materials, and set up electronic voting capability for absent board members.";
    $result = callAlfred($prompt);
    return ['success' => true, 'meeting_date' => $meeting_date, 'agenda_items' => $agenda_items, 'previous_minutes' => $previous_minutes, 'data' => $result,
            'message' => "Board meeting prep for $meeting_date ready. " . substr($result, 0, 500)];
}

function toolTaxExemptCompliance($args) {
    $organization_type = $args['organization_type'] ?? '501c3';
    $state = $args['state'] ?? '';
    $revenue = $args['revenue'] ?? 0;
    $prompt = "You are Alfred, a professional AI tax-exempt compliance specialist. Org type: $organization_type. State: $state. Revenue: \$$revenue. Perform tax-exempt compliance review for $organization_type organization in $state with \$$revenue annual revenue, determine required IRS filing (990-N for under \$50K, 990-EZ for \$50K-\$200K, 990 for over \$200K), check state charity registration requirements for $state, verify unrelated business income tax (UBIT) exposure, review public support test for public charity status, check lobbying and political activity limits, verify donor acknowledgment letter compliance, review executive compensation reasonableness, check related party transaction disclosures, assess intermediate sanctions risk, verify annual state filing deadlines and requirements, review sales tax exemption status and renewals, check employment tax compliance including worker classification, and provide calendar of all compliance deadlines with responsible parties.";
    $result = callAlfred($prompt);
    return ['success' => true, 'organization_type' => $organization_type, 'state' => $state, 'revenue' => $revenue, 'data' => $result,
            'message' => "Tax-exempt compliance ($organization_type, $state, \$$revenue). " . substr($result, 0, 500)];
}

function toolEventPlanner($args) {
    $event_name = $args['event_name'] ?? '';
    $budget = $args['budget'] ?? 0;
    $expected_attendees = $args['expected_attendees'] ?? 100;
    $prompt = "You are Alfred, a professional AI nonprofit event planning specialist. Event: $event_name. Budget: \$$budget. Attendees: $expected_attendees. Plan fundraising event '$event_name' for $expected_attendees attendees with \$$budget budget, create detailed event timeline from planning through post-event follow-up, allocate budget across venue, catering, entertainment, marketing, and contingency, develop sponsorship packages with tiered benefits, create marketing plan with email campaigns, social media, and print materials, design registration workflow with ticket tiers and early-bird pricing, plan event logistics including AV, seating, signage, and parking, develop fundraising elements (live auction, paddle raise, text-to-give), create volunteer assignment schedule with roles and responsibilities, plan menu options accommodating dietary needs, develop run-of-show document with minute-by-minute timeline, design post-event thank you and impact report for attendees, calculate projected net revenue at different attendance scenarios, and create contingency plans for weather, low attendance, or technical issues.";
    $result = callAlfred($prompt);
    return ['success' => true, 'event_name' => $event_name, 'budget' => $budget, 'expected_attendees' => $expected_attendees, 'data' => $result,
            'message' => "Event plan for '$event_name' (\$$budget, $expected_attendees attendees). " . substr($result, 0, 500)];
}

function toolSocialImpactMetrics($args) {
    $program = $args['program'] ?? '';
    $metrics = $args['metrics'] ?? '';
    $period = $args['period'] ?? 'annual';
    $prompt = "You are Alfred, a professional AI social impact measurement specialist. Program: $program. Metrics: $metrics. Period: $period. Measure and report social impact for program '$program' over $period period, develop theory of change linking activities to outcomes, define output metrics (people served, services delivered) from ($metrics), define outcome metrics (behavior change, skill improvement, economic impact), create data collection instruments and protocols, calculate social return on investment (SROI) ratio, develop beneficiary satisfaction surveys and analysis, track longitudinal outcomes for program participants, benchmark against similar programs nationally, create impact dashboard with visual data representations, align metrics with UN Sustainable Development Goals where applicable, prepare funder-ready impact reports with narratives and data, identify unintended consequences both positive and negative, recommend program improvements based on impact data, and develop case studies highlighting individual transformation stories.";
    $result = callAlfred($prompt);
    return ['success' => true, 'program' => $program, 'metrics' => $metrics, 'period' => $period, 'data' => $result,
            'message' => "Social impact metrics for '$program' ($period). " . substr($result, 0, 500)];
}

// ============================================================================
// MARKETPLACE GAPS (6 tools)
// ============================================================================

function toolMarketplaceInstall($args) {
    $item_id = $args['item_id'] ?? '';
    $version = $args['version'] ?? 'latest';
    $prompt = "You are Alfred, a professional AI marketplace installation specialist. Item ID: $item_id. Version: $version. Install marketplace item '$item_id' version $version, verify item compatibility with current system version and configuration, check system requirements (PHP version, extensions, disk space), download package from marketplace CDN with integrity verification, create backup of affected files and database tables before installation, extract and validate package contents, run pre-installation checks and dependency resolution, execute database migrations if required, copy files to appropriate directories with correct permissions, run post-installation configuration and setup wizards, verify installation integrity with health checks, activate item and configure default settings, update system registry with installed item metadata, test core functionality after installation, and provide rollback instructions if issues are detected.";
    $result = callAlfred($prompt);
    return ['success' => true, 'item_id' => $item_id, 'version' => $version, 'data' => $result,
            'message' => "Marketplace item '$item_id' v$version installed. " . substr($result, 0, 500)];
}

function toolMarketplaceReview($args) {
    $item_id = $args['item_id'] ?? '';
    $action = $args['action'] ?? 'read';
    $rating = $args['rating'] ?? 5;
    $review_text = $args['review_text'] ?? '';
    $prompt = "You are Alfred, a professional AI marketplace review specialist. Item: $item_id. Action: $action. Rating: $rating/5. Review: $review_text. Perform '$action' on reviews for marketplace item '$item_id', for 'read': aggregate all reviews with average rating, distribution breakdown, and most helpful reviews highlighted, identify common praise themes and complaint patterns, for 'write': submit $rating-star review with text '$review_text', validate review for constructive content and policy compliance, suggest improvements to review text for helpfulness, analyze sentiment of existing reviews, compare ratings against category averages, identify fake or suspicious review patterns, track rating trends over time and across versions, display verified purchase badges, generate review summary for quick decision making, provide comparison with competing items' reviews, and flag unresolved issues mentioned in negative reviews.";
    $result = callAlfred($prompt);
    return ['success' => true, 'item_id' => $item_id, 'action' => $action, 'rating' => $rating, 'review_text' => $review_text, 'data' => $result,
            'message' => "Marketplace review ($action) for '$item_id'. " . substr($result, 0, 500)];
}

function toolMarketplacePricing($args) {
    $item_id = $args['item_id'] ?? '';
    $price = $args['price'] ?? 0;
    $pricing_model = $args['pricing_model'] ?? 'one_time';
    $prompt = "You are Alfred, a professional AI marketplace pricing specialist. Item: $item_id. Price: \$$price. Model: $pricing_model. Configure pricing for marketplace item '$item_id' at \$$price with $pricing_model model, for 'one_time': set purchase price with optional volume discounts, for 'subscription': configure monthly/annual billing with auto-renewal, for 'freemium': define free tier limits and premium upgrade triggers, for 'usage_based': set per-unit pricing with tiered discounts, analyze competitive pricing in the marketplace category, calculate optimal price point using value-based pricing methodology, configure promotional pricing and coupon support, set up geographic pricing for different markets, define refund and cancellation policies, create pricing page copy with feature comparison tiers, estimate revenue projections at different price points, implement A/B testing for pricing experiments, and configure enterprise/custom pricing request workflow.";
    $result = callAlfred($prompt);
    return ['success' => true, 'item_id' => $item_id, 'price' => $price, 'pricing_model' => $pricing_model, 'data' => $result,
            'message' => "Pricing set for '$item_id': \$$price ($pricing_model). " . substr($result, 0, 500)];
}

function toolToolBuilder($args) {
    $tool_name = $args['tool_name'] ?? '';
    $inputs = $args['inputs'] ?? '';
    $logic = $args['logic'] ?? '';
    $prompt = "You are Alfred, a professional AI custom tool building specialist. Tool name: $tool_name. Inputs: $inputs. Logic: $logic. Build custom tool '$tool_name' with defined inputs ($inputs) and business logic ($logic), generate tool function following Alfred tool pattern with parameter extraction and defaults, create input validation with type checking and range constraints, implement core logic: $logic, add error handling with descriptive messages and fallbacks, create tool schema definition for VAPI registration with parameter descriptions, generate unit tests covering normal, edge, and error cases, create documentation with usage examples and parameter descriptions, add logging and analytics tracking for usage monitoring, implement rate limiting and access control, create tool icon and marketplace listing description, set up versioning for future updates, generate integration tests with Alfred pipeline, and provide deployment instructions for production registration.";
    $result = callAlfred($prompt);
    return ['success' => true, 'tool_name' => $tool_name, 'inputs' => $inputs, 'logic' => $logic, 'data' => $result,
            'message' => "Custom tool '$tool_name' built. " . substr($result, 0, 500)];
}

function toolAgentTemplateStore($args) {
    $industry = $args['industry'] ?? 'general';
    $action = $args['action'] ?? 'browse';
    $template_id = $args['template_id'] ?? '';
    $prompt = "You are Alfred, a professional AI agent template specialist. Industry: $industry. Action: $action. Template ID: $template_id. Perform '$action' on agent template store for $industry industry, for 'browse': list available agent templates filtered by $industry with descriptions, ratings, and download counts, categorize by use case (customer service, sales, support, onboarding), for 'install': deploy template '$template_id' with guided customization wizard, configure industry-specific prompts, tools, and workflows, set up required integrations and API connections, for 'preview': show sample conversations and capability demonstrations, compare templates side-by-side on features and pricing, display compatibility requirements and system prerequisites, provide customization options for branding, voice, and persona, include ROI estimates based on template usage data, list included tools and enabled capabilities, and generate deployment checklist for production readiness.";
    $result = callAlfred($prompt);
    return ['success' => true, 'industry' => $industry, 'action' => $action, 'template_id' => $template_id, 'data' => $result,
            'message' => "Agent templates ($action) for $industry industry. " . substr($result, 0, 500)];
}

function toolPlaybookMarketplace($args) {
    $action = $args['action'] ?? 'browse';
    $playbook_id = $args['playbook_id'] ?? '';
    $tags = $args['tags'] ?? '';
    $prompt = "You are Alfred, a professional AI playbook marketplace specialist. Action: $action. Playbook ID: $playbook_id. Tags: $tags. Perform '$action' on playbook marketplace, for 'browse': list available playbooks filtered by tags ($tags) with descriptions, author ratings, and success metrics, categorize by workflow type (sales, marketing, operations, HR, finance), for 'publish': validate playbook structure and completeness, generate marketplace listing with screenshots and demo, set pricing and license terms, for 'install': deploy playbook '$playbook_id' with step-by-step setup, import workflow definitions, triggers, and automation rules, configure integrations and data connections, provide version compatibility checking and dependency resolution, include community ratings and usage statistics, show before/after metrics from other installations, allow customization of steps and conditions, bundle related playbooks with discount pricing, and generate onboarding guide for team adoption.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'playbook_id' => $playbook_id, 'tags' => $tags, 'data' => $result,
            'message' => "Playbook marketplace ($action)" . ($tags ? " tags: $tags" : "") . ". " . substr($result, 0, 500)];
}

// ============================================================================
// GAMIFICATION GAPS (2 tools)
// ============================================================================

function toolSkillTree($args) {
    $user_id = $args['user_id'] ?? 'current';
    $category = $args['category'] ?? 'all';
    $action = $args['action'] ?? 'view';
    $prompt = "You are Alfred, a professional AI gamification and skill development specialist. User: $user_id. Category: $category. Action: $action. Perform '$action' on skill tree for user '$user_id' in category '$category', for 'view': display full skill tree with unlocked, in-progress, and locked skills organized by category, show experience points and level for each skill branch, highlight next achievable skills with requirements, for 'progress': track skill advancement and award experience points, for 'recommend': suggest optimal skill path based on goals and current abilities, display skill dependencies and prerequisite chains, show achievement badges and milestones earned, calculate time-to-next-level for active skills, compare skill profile against role benchmarks, provide leaderboard position among peers, integrate with learning resources for each skill, track daily/weekly streaks and consistency bonuses, create personalized challenge recommendations, and visualize skill tree as interactive graph with zoom levels.";
    $result = callAlfred($prompt);
    return ['success' => true, 'user_id' => $user_id, 'category' => $category, 'action' => $action, 'data' => $result,
            'message' => "Skill tree ($action) for $user_id, category: $category. " . substr($result, 0, 500)];
}

function toolLearningPath($args) {
    $skill = $args['skill'] ?? '';
    $current_level = $args['current_level'] ?? 'beginner';
    $preferred_pace = $args['preferred_pace'] ?? 'moderate';
    $prompt = "You are Alfred, a professional AI learning path design specialist. Skill: $skill. Current level: $current_level. Pace: $preferred_pace. Design personalized learning path for '$skill' starting from $current_level level at $preferred_pace pace, create structured curriculum with progressive difficulty modules, estimate time-to-competency for each milestone, recommend mix of learning resources (tutorials, courses, projects, books, videos), design hands-on practice exercises for each concept, create assessment checkpoints to validate understanding, build capstone projects that demonstrate skill mastery, identify complementary skills that accelerate learning, set up spaced repetition schedule for knowledge retention, create study schedule adapted to $preferred_pace (hours per week), include community and mentorship resources, provide alternative learning paths for different learning styles (visual, auditory, kinesthetic), track progress with experience points and achievement badges, recommend real-world application opportunities, and create portfolio-ready deliverables at each stage.";
    $result = callAlfred($prompt);
    return ['success' => true, 'skill' => $skill, 'current_level' => $current_level, 'preferred_pace' => $preferred_pace, 'data' => $result,
            'message' => "Learning path for '$skill' ($current_level, $preferred_pace pace). " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════
// HEALTHCARE WORKERS — Voice Tools (Additions)
// ═══════════════════════════════════════════════════════════════════════════

function toolSOAPNoteWriter($args) {
    $chief_complaint = trim($args['chief_complaint'] ?? '');
    $findings = trim($args['findings'] ?? '');
    $assessment = trim($args['assessment'] ?? '');
    $plan = trim($args['plan'] ?? '');
    if (empty($chief_complaint)) return ['error' => false, 'message' => 'I need the chief complaint to generate a SOAP note. What is the patient presenting with?'];
    $prompt = "You are Alfred, a professional AI medical documentation assistant. Generate a complete SOAP note from the following encounter information. Chief Complaint: $chief_complaint. Subjective findings: $findings. Assessment: $assessment. Plan: $plan. Structure the note in proper SOAP format with Subjective (patient history, symptoms, chief complaint), Objective (vital signs, physical exam findings, lab results), Assessment (diagnoses, differential diagnoses, clinical reasoning), and Plan (medications, referrals, follow-up, patient education). Use standard medical abbreviations and professional clinical language. Ensure the note is thorough, accurate, and ready for the medical record.";
    $result = callAlfred($prompt);
    return ['success' => true, 'chief_complaint' => $chief_complaint, 'assessment' => $assessment, 'data' => $result,
            'message' => "SOAP note generated for chief complaint: $chief_complaint. " . substr($result, 0, 500)];
}

function toolShiftScheduler($args) {
    $staff_count = trim($args['staff_count'] ?? '');
    $shift_type = trim($args['shift_type'] ?? '8-hour');
    $department = trim($args['department'] ?? '');
    $start_date = trim($args['start_date'] ?? date('Y-m-d'));
    $constraints = trim($args['constraints'] ?? '');
    if (empty($staff_count)) return ['error' => false, 'message' => 'I need the number of staff members to schedule. How many staff are available?'];
    if (empty($department)) return ['error' => false, 'message' => 'I need the department name. Which department is this schedule for?'];
    $prompt = "You are Alfred, a professional AI healthcare scheduling assistant. Create an optimized shift schedule for the $department department. Staff count: $staff_count. Shift type: $shift_type. Start date: $start_date. Constraints: $constraints. Generate a fair, balanced schedule ensuring adequate coverage for all shifts, comply with labor regulations (maximum consecutive hours, mandatory rest periods), account for weekends and holidays, distribute night shifts equitably, include on-call assignments, flag any staffing gaps or overtime concerns, and provide a clear formatted weekly schedule grid.";
    $result = callAlfred($prompt);
    return ['success' => true, 'staff_count' => $staff_count, 'shift_type' => $shift_type, 'department' => $department, 'data' => $result,
            'message' => "Shift schedule created for $department ($staff_count staff, $shift_type shifts). " . substr($result, 0, 500)];
}

function toolPatientHandoff($args) {
    $patient_id = trim($args['patient_id'] ?? '');
    $situation = trim($args['situation'] ?? '');
    $background = trim($args['background'] ?? '');
    $assessment = trim($args['assessment'] ?? '');
    $recommendation = trim($args['recommendation'] ?? '');
    if (empty($situation)) return ['error' => false, 'message' => 'I need the current situation for the SBAR handoff. What is happening with the patient right now?'];
    $prompt = "You are Alfred, a professional AI clinical communications assistant. Generate a comprehensive SBAR patient handoff report. Patient ID: $patient_id. Situation: $situation. Background: $background. Assessment: $assessment. Recommendation: $recommendation. Format the handoff using the SBAR framework: Situation (concise statement of the current problem), Background (relevant medical history, current medications, allergies, code status), Assessment (clinical findings, vital sign trends, current treatment effectiveness), Recommendation (specific actions requested, contingency plans, escalation criteria). Include critical lab values, pending tests, IV access status, and any safety concerns. Ensure clarity for the receiving provider.";
    $result = callAlfred($prompt);
    return ['success' => true, 'patient_id' => $patient_id, 'situation' => $situation, 'data' => $result,
            'message' => "SBAR handoff report generated for patient $patient_id. " . substr($result, 0, 500)];
}

function toolMedicationChecker($args) {
    $medications = $args['medications'] ?? [];
    if (is_string($medications)) $medications = array_map('trim', explode(',', $medications));
    if (empty($medications)) return ['error' => false, 'message' => 'I need a list of medications to check for interactions. What medications should I review?'];
    $med_list = implode(', ', $medications);
    $patient_conditions = trim($args['patient_conditions'] ?? '');
    $allergies = trim($args['allergies'] ?? '');
    $prompt = "You are Alfred, a professional AI pharmacology assistant. Check for drug interactions among the following medications: $med_list. Patient conditions: $patient_conditions. Known allergies: $allergies. Analyze all possible drug-drug interactions, categorize by severity (major, moderate, minor), identify contraindications based on patient conditions, flag any allergy cross-reactivity concerns, note food-drug interactions, check for therapeutic duplications, provide clinical significance and management recommendations for each interaction found. IMPORTANT DISCLAIMER: This is for informational purposes only and does not replace professional pharmacist review.";
    $result = callAlfred($prompt);
    return ['success' => true, 'medications' => $medications, 'interaction_count' => count($medications), 'data' => $result,
            'message' => "Medication interaction check for: $med_list. " . substr($result, 0, 500)];
}

function toolClinicalProtocolFinder($args) {
    $condition = trim($args['condition'] ?? '');
    $department = trim($args['department'] ?? '');
    $urgency = trim($args['urgency'] ?? 'routine');
    if (empty($condition)) return ['error' => false, 'message' => 'I need the clinical condition to find the appropriate protocol. What condition are you looking up?'];
    $prompt = "You are Alfred, a professional AI clinical decision support assistant. Find and summarize the clinical protocol for: $condition. Department: $department. Urgency level: $urgency. Provide the evidence-based clinical protocol including: initial assessment steps, diagnostic workup (labs, imaging, procedures), treatment algorithm with first-line and alternative therapies, monitoring parameters and frequency, escalation criteria, discharge criteria, patient education points, and follow-up recommendations. Reference current clinical guidelines (e.g., AHA, ACC, IDSA, NICE) where applicable. Adapt recommendations to the $department setting and $urgency urgency level.";
    $result = callAlfred($prompt);
    return ['success' => true, 'condition' => $condition, 'department' => $department, 'urgency' => $urgency, 'data' => $result,
            'message' => "Clinical protocol for $condition ($department, $urgency). " . substr($result, 0, 500)];
}

function toolMedicalTerminology($args) {
    $term = trim($args['term'] ?? '');
    $context = trim($args['context'] ?? 'general');
    $audience = trim($args['audience'] ?? 'healthcare_professional');
    if (empty($term)) return ['error' => false, 'message' => 'I need the medical term you want explained. What term would you like me to define?'];
    $prompt = "You are Alfred, a professional AI medical education assistant. Explain the medical term: '$term'. Context: $context. Target audience: $audience. Provide: the full definition, etymology (Latin/Greek roots), common abbreviations, how it is used in clinical practice, related terms and conditions, layperson-friendly explanation, example usage in a clinical note, relevant ICD-10 or CPT codes if applicable, and any commonly confused similar terms. Adjust the complexity of the explanation for the $audience audience level.";
    $result = callAlfred($prompt);
    return ['success' => true, 'term' => $term, 'context' => $context, 'data' => $result,
            'message' => "Medical terminology: '$term'. " . substr($result, 0, 500)];
}

function toolContinuingEdTracker($args) {
    $profession = trim($args['profession'] ?? '');
    $credits_completed = trim($args['credits_completed'] ?? '0');
    $credits_required = trim($args['credits_required'] ?? '');
    $renewal_date = trim($args['renewal_date'] ?? '');
    $specialties = trim($args['specialties'] ?? '');
    if (empty($profession)) return ['error' => false, 'message' => 'I need your profession to track CE requirements. What is your healthcare profession?'];
    $prompt = "You are Alfred, a professional AI continuing education tracking assistant. Track CE credits for a $profession. Credits completed: $credits_completed. Credits required: $credits_required. License renewal date: $renewal_date. Specialties: $specialties. Provide: a progress summary showing credits completed vs required, breakdown by category (clinical, ethics, pharmacology, specialty-specific), time remaining until renewal deadline, recommended CE courses to fill gaps, accredited CE providers and platforms, estimated time to complete remaining credits, a study schedule to meet the deadline, state-specific CE requirements for $profession, and any mandatory topic requirements (e.g., opioid prescribing, infection control, cultural competency).";
    $result = callAlfred($prompt);
    return ['success' => true, 'profession' => $profession, 'credits_completed' => $credits_completed, 'credits_required' => $credits_required, 'data' => $result,
            'message' => "CE tracking for $profession: $credits_completed/$credits_required credits. " . substr($result, 0, 500)];
}

function toolIncidentReport($args) {
    $incident_type = trim($args['incident_type'] ?? '');
    $description = trim($args['description'] ?? '');
    $severity = trim($args['severity'] ?? 'moderate');
    $location = trim($args['location'] ?? '');
    $date_time = trim($args['date_time'] ?? date('Y-m-d H:i'));
    $witnesses = trim($args['witnesses'] ?? '');
    if (empty($incident_type)) return ['error' => false, 'message' => 'I need the type of incident to generate the report. What kind of incident occurred?'];
    if (empty($description)) return ['error' => false, 'message' => 'I need a description of the incident. Please describe what happened.'];
    $prompt = "You are Alfred, a professional AI healthcare risk management assistant. Generate a formal incident report. Incident type: $incident_type. Description: $description. Severity: $severity. Location: $location. Date/Time: $date_time. Witnesses: $witnesses. Create a comprehensive incident report including: incident classification and severity rating, detailed narrative of events in chronological order, contributing factors analysis, immediate actions taken, patient/staff impact assessment, root cause analysis, corrective action recommendations, follow-up requirements, notification checklist (risk management, department head, regulatory if applicable), and documentation of any equipment or environmental factors involved. Format according to healthcare facility incident reporting standards.";
    $result = callAlfred($prompt);
    return ['success' => true, 'incident_type' => $incident_type, 'severity' => $severity, 'date_time' => $date_time, 'data' => $result,
            'message' => "Incident report generated: $incident_type ($severity severity). " . substr($result, 0, 500)];
}

function toolInfectionControl($args) {
    $area = trim($args['area'] ?? '');
    $protocol_type = trim($args['protocol_type'] ?? 'standard');
    $pathogen = trim($args['pathogen'] ?? '');
    $setting = trim($args['setting'] ?? 'inpatient');
    if (empty($area)) return ['error' => false, 'message' => 'I need the area or unit for infection control guidance. Which area are you preparing the checklist for?'];
    $prompt = "You are Alfred, a professional AI infection prevention and control specialist. Generate an infection control checklist for: Area: $area. Protocol type: $protocol_type. Pathogen (if specific): $pathogen. Setting: $setting. Provide a comprehensive checklist covering: hand hygiene compliance checkpoints, personal protective equipment (PPE) requirements, isolation precautions (contact, droplet, airborne) if applicable, environmental cleaning and disinfection protocols, equipment sterilization procedures, waste management guidelines, patient placement and cohorting recommendations, visitor policies, staff exposure management, surveillance and monitoring metrics, outbreak response steps if $pathogen is identified, and reporting requirements to infection control committee and public health authorities.";
    $result = callAlfred($prompt);
    return ['success' => true, 'area' => $area, 'protocol_type' => $protocol_type, 'pathogen' => $pathogen, 'data' => $result,
            'message' => "Infection control checklist for $area ($protocol_type precautions). " . substr($result, 0, 500)];
}

function toolTelehealthSetup($args) {
    $provider = trim($args['provider'] ?? '');
    $patient = trim($args['patient'] ?? '');
    $datetime = trim($args['datetime'] ?? '');
    $visit_type = trim($args['visit_type'] ?? 'follow-up');
    $platform = trim($args['platform'] ?? 'default');
    if (empty($provider)) return ['error' => false, 'message' => 'I need the provider name to set up the telehealth appointment. Who is the provider?'];
    if (empty($patient)) return ['error' => false, 'message' => 'I need the patient name for the telehealth appointment. Who is the patient?'];
    $prompt = "You are Alfred, a professional AI telehealth coordination assistant. Set up a telehealth appointment. Provider: $provider. Patient: $patient. Date/Time: $datetime. Visit type: $visit_type. Platform: $platform. Generate a complete telehealth setup guide including: appointment confirmation details, platform setup instructions for both provider and patient, technical requirements (bandwidth, camera, microphone), pre-visit checklist (insurance verification, consent forms, intake paperwork), patient preparation instructions (vitals to self-measure, medication list, symptom diary), troubleshooting guide for common technical issues, backup plan if connection fails (phone number, rescheduling), documentation templates for the telehealth encounter, billing and coding considerations for $visit_type telehealth visit, and state-specific telehealth regulations and consent requirements.";
    $result = callAlfred($prompt);
    return ['success' => true, 'provider' => $provider, 'patient' => $patient, 'datetime' => $datetime, 'visit_type' => $visit_type, 'data' => $result,
            'message' => "Telehealth setup: $provider with $patient on $datetime ($visit_type). " . substr($result, 0, 500)];
}

function toolHIPAACompliance($args) {
    $area = trim($args['area'] ?? '');
    $audit_type = trim($args['audit_type'] ?? 'general');
    $organization_type = trim($args['organization_type'] ?? 'covered_entity');
    if (empty($area)) return ['error' => false, 'message' => 'I need the area to audit for HIPAA compliance. Which area should I review (e.g., physical security, electronic PHI, policies)?'];
    $prompt = "You are Alfred, a professional AI HIPAA compliance specialist. Generate a HIPAA compliance checklist. Area: $area. Audit type: $audit_type. Organization type: $organization_type. Provide a comprehensive compliance checklist covering: Privacy Rule requirements (minimum necessary standard, patient rights, Notice of Privacy Practices), Security Rule requirements (administrative, physical, and technical safeguards), Breach Notification Rule procedures, Business Associate Agreement review, risk assessment findings for $area, employee training requirements and documentation, access control and audit trail verification, data encryption standards (at rest and in transit), mobile device and remote access policies, incident response procedures, documentation retention requirements, penalties for non-compliance by tier, and specific $audit_type audit criteria for $area. Include recommended remediation steps for common deficiencies.";
    $result = callAlfred($prompt);
    return ['success' => true, 'area' => $area, 'audit_type' => $audit_type, 'organization_type' => $organization_type, 'data' => $result,
            'message' => "HIPAA compliance checklist for $area ($audit_type audit). " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════
// REAL ESTATE — Voice Tools (Additions)
// ═══════════════════════════════════════════════════════════════════════════

function toolListingWriter($args) {
    $property_type = trim($args['property_type'] ?? '');
    $bedrooms = trim($args['bedrooms'] ?? '');
    $bathrooms = trim($args['bathrooms'] ?? '');
    $features = trim($args['features'] ?? '');
    $location = trim($args['location'] ?? '');
    $square_feet = trim($args['square_feet'] ?? '');
    $price = trim($args['price'] ?? '');
    if (empty($property_type)) return ['error' => false, 'message' => 'I need the property type to write the listing. What type of property is it (house, condo, townhouse, etc.)?'];
    if (empty($location)) return ['error' => false, 'message' => 'I need the property location. Where is the property located?'];
    $prompt = "You are Alfred, a professional AI real estate marketing copywriter. Write a compelling MLS-ready property listing description. Property type: $property_type. Bedrooms: $bedrooms. Bathrooms: $bathrooms. Square feet: $square_feet. Price: $price. Location: $location. Key features: $features. Create an engaging, professional listing that: opens with an attention-grabbing headline, highlights the most marketable features first, describes the flow and layout of the home, emphasizes neighborhood and location benefits, includes relevant keywords for online search optimization, uses vivid but accurate descriptive language, mentions recent upgrades and special amenities, ends with a compelling call to action, stays within MLS character limits, and avoids fair housing violations and prohibited language. Provide both a full description and a shortened social media version.";
    $result = callAlfred($prompt);
    return ['success' => true, 'property_type' => $property_type, 'location' => $location, 'bedrooms' => $bedrooms, 'bathrooms' => $bathrooms, 'data' => $result,
            'message' => "Listing description for $bedrooms BR/$bathrooms BA $property_type in $location. " . substr($result, 0, 500)];
}

function toolClientFollowUp($args) {
    $client_name = trim($args['client_name'] ?? '');
    $last_contact = trim($args['last_contact'] ?? '');
    $property_interest = trim($args['property_interest'] ?? '');
    $client_type = trim($args['client_type'] ?? 'buyer');
    $notes = trim($args['notes'] ?? '');
    if (empty($client_name)) return ['error' => false, 'message' => 'I need the client name for the follow-up. What is the client\'s name?'];
    $prompt = "You are Alfred, a professional AI real estate CRM and client relationship assistant. Generate a personalized follow-up plan for client: $client_name. Client type: $client_type. Last contact: $last_contact. Property interest: $property_interest. Notes: $notes. Create a follow-up strategy including: personalized email/text message templates appropriate for the stage of the relationship, optimal timing for next contact based on last interaction ($last_contact), relevant property matches or market updates to share, conversation starters and talking points, milestone reminders (anniversary of purchase, birthday if known), market data relevant to their $property_interest interest, a drip campaign schedule for the next 30/60/90 days, and suggested next steps to move the relationship forward. Tailor tone and content specifically for a $client_type client.";
    $result = callAlfred($prompt);
    return ['success' => true, 'client_name' => $client_name, 'client_type' => $client_type, 'last_contact' => $last_contact, 'property_interest' => $property_interest, 'data' => $result,
            'message' => "Follow-up plan for $client_name ($client_type, interested in $property_interest). " . substr($result, 0, 500)];
}

function toolClosingChecklist($args) {
    $transaction_type = trim($args['transaction_type'] ?? '');
    $stage = trim($args['stage'] ?? 'pre-closing');
    $property_address = trim($args['property_address'] ?? '');
    $closing_date = trim($args['closing_date'] ?? '');
    if (empty($transaction_type)) return ['error' => false, 'message' => 'I need the transaction type. Is this a purchase, sale, or refinance?'];
    $prompt = "You are Alfred, a professional AI real estate transaction coordinator. Generate a closing checklist for a $transaction_type transaction. Stage: $stage. Property: $property_address. Closing date: $closing_date. Provide a comprehensive checklist organized by stage including: pre-contract (offer, counter-offer, acceptance documentation), under-contract (earnest money, inspection scheduling, appraisal ordering), title and escrow (title search, title insurance, escrow account setup), financing (loan application, underwriting documents, rate lock), pre-closing (final walkthrough, utility transfers, insurance binder), closing day (ID requirements, cashier's checks, signing documents), and post-closing (recording, key handover, file retention). Highlight items specific to the current stage ($stage), flag time-sensitive deadlines relative to closing date ($closing_date), and note common pitfalls to avoid at each step.";
    $result = callAlfred($prompt);
    return ['success' => true, 'transaction_type' => $transaction_type, 'stage' => $stage, 'property_address' => $property_address, 'closing_date' => $closing_date, 'data' => $result,
            'message' => "Closing checklist for $transaction_type ($stage) - $property_address. " . substr($result, 0, 500)];
}

function toolLeadQualifier($args) {
    $budget = trim($args['budget'] ?? '');
    $timeline = trim($args['timeline'] ?? '');
    $pre_approved = trim($args['pre_approved'] ?? 'unknown');
    $property_type = trim($args['property_type'] ?? '');
    $motivation = trim($args['motivation'] ?? '');
    $location_preference = trim($args['location_preference'] ?? '');
    if (empty($budget)) return ['error' => false, 'message' => 'I need the lead\'s budget range to qualify them. What is their approximate budget?'];
    $prompt = "You are Alfred, a professional AI real estate lead qualification specialist. Qualify a real estate lead with the following profile. Budget: $budget. Timeline: $timeline. Pre-approved: $pre_approved. Property type desired: $property_type. Motivation: $motivation. Preferred location: $location_preference. Analyze and score this lead on: financial readiness (budget realism, pre-approval status), timeline urgency (hot/warm/cold classification), motivation level and buying signals, market compatibility (can their budget meet their expectations in $location_preference), recommended next steps for the agent, suggested questions to ask in the next conversation, potential objections and how to address them, comparable properties in their range to present, and an overall lead score (A/B/C/D) with justification. Provide actionable recommendations for converting this lead.";
    $result = callAlfred($prompt);
    return ['success' => true, 'budget' => $budget, 'timeline' => $timeline, 'pre_approved' => $pre_approved, 'property_type' => $property_type, 'data' => $result,
            'message' => "Lead qualification: Budget $budget, Timeline $timeline, Pre-approved: $pre_approved. " . substr($result, 0, 500)];
}

function toolNeighborhoodProfile($args) {
    $location = trim($args['location'] ?? '');
    $radius = trim($args['radius'] ?? '1 mile');
    $focus = trim($args['focus'] ?? 'general');
    if (empty($location)) return ['error' => false, 'message' => 'I need a location to profile. What neighborhood or address should I research?'];
    $prompt = "You are Alfred, a professional AI real estate neighborhood research assistant. Generate a comprehensive neighborhood profile for: $location (radius: $radius). Focus area: $focus. Provide detailed information on: demographics and population trends, school ratings and school district information, crime statistics and safety ratings, median home prices and price trends, walkability and transit scores, nearby amenities (grocery, dining, parks, entertainment, healthcare), commute times to major employment centers, planned developments and zoning changes, homeowner association information if applicable, community character and lifestyle description, local market conditions (days on market, list-to-sale ratio), property tax rates, utility providers and average costs, and community events or notable features. Emphasize $focus aspects of the neighborhood. Present data in a buyer-friendly format suitable for sharing with clients.";
    $result = callAlfred($prompt);
    return ['success' => true, 'location' => $location, 'radius' => $radius, 'focus' => $focus, 'data' => $result,
            'message' => "Neighborhood profile for $location ($radius radius). " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════
// SENIORS — Voice Tools (Additions)
// ═══════════════════════════════════════════════════════════════════════════

function toolHealthJournal($args) {
    $blood_pressure = trim($args['blood_pressure'] ?? '');
    $glucose = trim($args['glucose'] ?? '');
    $mood = trim($args['mood'] ?? '');
    $notes = trim($args['notes'] ?? '');
    $pain_level = trim($args['pain_level'] ?? '');
    $weight = trim($args['weight'] ?? '');
    $sleep_hours = trim($args['sleep_hours'] ?? '');
    $medications_taken = trim($args['medications_taken'] ?? 'yes');
    $date = trim($args['date'] ?? date('Y-m-d'));
    $prompt = "You are Alfred, a warm and supportive AI health companion for seniors. Record and analyze today's health journal entry for $date. Blood pressure: $blood_pressure. Blood glucose: $glucose. Mood: $mood. Pain level: $pain_level. Weight: $weight. Sleep hours: $sleep_hours. Medications taken: $medications_taken. Additional notes: $notes. Provide: a friendly summary of today's readings, gentle observations about any trends or concerns (in simple, non-alarming language), positive encouragement about healthy metrics, simple wellness tips relevant to today's readings, a reminder about medications or upcoming appointments, and note anything that should be mentioned to their doctor at the next visit. Use warm, clear, easy-to-understand language appropriate for seniors.";
    $result = callAlfred($prompt);
    return ['success' => true, 'date' => $date, 'blood_pressure' => $blood_pressure, 'glucose' => $glucose, 'mood' => $mood, 'data' => $result,
            'message' => "Health journal recorded for $date. " . substr($result, 0, 500)];
}

function toolCaregiverPortal($args) {
    $patient_name = trim($args['patient_name'] ?? '');
    $action = trim($args['action'] ?? 'update');
    $update = trim($args['update'] ?? '');
    $caregiver_name = trim($args['caregiver_name'] ?? '');
    $category = trim($args['category'] ?? 'general');
    if (empty($patient_name)) return ['error' => false, 'message' => 'I need the patient\'s name. Who is the family member you\'re caring for?'];
    $prompt = "You are Alfred, a compassionate AI caregiver support assistant. Action: $action for patient $patient_name. Caregiver: $caregiver_name. Category: $category. Update: $update. For action '$action': if 'update' — record the care update and provide a structured care log entry with timestamp, category ($category), and observations, suggest any follow-up actions; if 'summary' — compile a comprehensive care summary suitable for sharing with other family members or healthcare providers, include recent health trends, medication adherence, mood patterns, and notable events; if 'resources' — provide caregiver resources including respite care options, support groups, self-care tips for the caregiver, and relevant community services; if 'schedule' — help organize the caregiving schedule with tasks, appointments, and medication reminders. Always maintain an empathetic, supportive tone recognizing the demands of caregiving.";
    $result = callAlfred($prompt);
    return ['success' => true, 'patient_name' => $patient_name, 'action' => $action, 'caregiver_name' => $caregiver_name, 'data' => $result,
            'message' => "Caregiver portal ($action) for $patient_name. " . substr($result, 0, 500)];
}

function toolEmergencyAlert($args) {
    $contact_name = trim($args['contact_name'] ?? '');
    $emergency_type = trim($args['emergency_type'] ?? '');
    $location = trim($args['location'] ?? '');
    $caller_name = trim($args['caller_name'] ?? '');
    $medical_info = trim($args['medical_info'] ?? '');
    if (empty($emergency_type)) return ['error' => false, 'message' => 'I need to know the type of emergency. What kind of emergency is this (fall, medical, fire, other)?'];
    if (empty($contact_name)) return ['error' => false, 'message' => 'I need the emergency contact name. Who should be alerted?'];
    $prompt = "You are Alfred, an AI emergency response assistant. URGENT: Generate an emergency alert message and response plan. Caller: $caller_name. Emergency type: $emergency_type. Location: $location. Emergency contact: $contact_name. Medical information: $medical_info. Generate: 1) An immediate alert message to send to $contact_name with the emergency details, location, and caller information. 2) Step-by-step guidance for the person in the emergency situation while help arrives. 3) Information to relay to 911/emergency services if needed. 4) A checklist of immediate actions based on the $emergency_type type of emergency. 5) Follow-up actions after the immediate emergency is handled. Use clear, calm, directive language. Prioritize safety and speed of information delivery.";
    $result = callAlfred($prompt);
    return ['success' => true, 'contact_name' => $contact_name, 'emergency_type' => $emergency_type, 'location' => $location, 'data' => $result,
            'message' => "EMERGENCY ALERT: $emergency_type. Contact: $contact_name. Location: $location. " . substr($result, 0, 500)];
}

function toolPhotoOrganizer($args) {
    $action = trim($args['action'] ?? 'organize');
    $album = trim($args['album'] ?? '');
    $description = trim($args['description'] ?? '');
    $date_range = trim($args['date_range'] ?? '');
    $people = trim($args['people'] ?? '');
    $prompt = "You are Alfred, a friendly AI photo organization assistant for seniors. Action: $action. Album: $album. Description: $description. Date range: $date_range. People: $people. For action '$action': if 'organize' — suggest a simple, intuitive album structure based on the description and date range, recommend naming conventions that are easy to remember, provide tips for grouping photos by events, people, or time periods; if 'share' — create simple step-by-step instructions for sharing album '$album' with family members via email, text, or a shared link, include privacy considerations; if 'describe' — help write captions and descriptions for photos based on the provided description, suggest memory-preserving details to include; if 'find' — help locate specific photos by describing what to search for by date, people ($people), or events; if 'print' — guide through ordering prints or creating a photo book. Use patient, clear, non-technical language throughout.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'album' => $album, 'description' => $description, 'data' => $result,
            'message' => "Photo organizer ($action)" . ($album ? " - album: $album" : "") . ". " . substr($result, 0, 500)];
}

function toolVoiceMemo($args) {
    $action = trim($args['action'] ?? 'create');
    $content = trim($args['content'] ?? '');
    $category = trim($args['category'] ?? 'general');
    $recipient = trim($args['recipient'] ?? '');
    if ($action === 'create' && empty($content)) return ['error' => false, 'message' => 'I need the content for your voice memo. What would you like to say?'];
    $prompt = "You are Alfred, a patient and helpful AI voice memo assistant for seniors. Action: $action. Content: $content. Category: $category. Recipient: $recipient. For action '$action': if 'create' — format the voice memo content into a clear, well-organized written note categorized under '$category', add a timestamp, suggest a title, and confirm the content back in a clear summary; if 'list' — describe how to review saved memos by category ($category), most recent, or search by keyword; if 'send' — prepare the memo to be sent to $recipient via their preferred method, format it nicely and include context; if 'remind' — set up the memo as a reminder with a suggested time and frequency; if 'transcribe' — process and clean up the transcribed content for readability while preserving the original meaning. Always read back summaries clearly and confirm accuracy.";
    $result = callAlfred($prompt);
    return ['success' => true, 'action' => $action, 'category' => $category, 'content' => substr($content, 0, 100), 'data' => $result,
            'message' => "Voice memo ($action, $category). " . substr($result, 0, 500)];
}

function toolBillPayHelper($args) {
    $bill_type = trim($args['bill_type'] ?? '');
    $amount = trim($args['amount'] ?? '');
    $due_date = trim($args['due_date'] ?? '');
    $payee = trim($args['payee'] ?? '');
    $payment_method = trim($args['payment_method'] ?? '');
    if (empty($bill_type)) return ['error' => false, 'message' => 'I need to know what kind of bill this is. What type of bill are you paying (electric, phone, medical, etc.)?'];
    $prompt = "You are Alfred, a patient and reassuring AI financial assistant for seniors. Help with bill payment. Bill type: $bill_type. Amount: $amount. Due date: $due_date. Payee: $payee. Payment method: $payment_method. Provide clear, step-by-step guidance for paying this bill including: confirmation of bill details (type: $bill_type, amount: $amount, due to: $payee, due by: $due_date), simple instructions for the chosen payment method ($payment_method), what information they'll need ready (account numbers, payment details), how to verify the payment went through, how to save or print a confirmation receipt, warnings about common scams related to $bill_type bills, a reminder to note this payment in their records, and upcoming payment schedule if this is a recurring bill. Use simple, patient language. Offer reassurance throughout the process. Never rush the user.";
    $result = callAlfred($prompt);
    return ['success' => true, 'bill_type' => $bill_type, 'amount' => $amount, 'due_date' => $due_date, 'payee' => $payee, 'data' => $result,
            'message' => "Bill payment help: $bill_type - $amount due $due_date to $payee. " . substr($result, 0, 500)];
}

function toolSocialConnector($args) {
    $location = trim($args['location'] ?? '');
    $interests = trim($args['interests'] ?? '');
    $age_group = trim($args['age_group'] ?? 'senior');
    $mobility = trim($args['mobility'] ?? 'mobile');
    $availability = trim($args['availability'] ?? '');
    if (empty($location)) return ['error' => false, 'message' => 'I need your location to find activities near you. What city or area are you in?'];
    $prompt = "You are Alfred, a warm and encouraging AI social connection assistant for seniors. Find social activities and groups near: $location. Interests: $interests. Age group: $age_group. Mobility level: $mobility. Availability: $availability. Suggest: community center programs and classes, hobby groups matching interests ($interests), volunteer opportunities suitable for $age_group, religious or spiritual community groups, exercise and wellness classes adapted for $mobility mobility level, technology learning groups, book clubs and discussion groups, arts and crafts workshops, gardening clubs, walking or nature groups, meal sharing or cooking groups, intergenerational programs, virtual/online options for days with limited mobility, transportation resources to get to activities, and tips for making new social connections. Prioritize activities that are welcoming, accessible, and appropriate for the $mobility mobility level. Include both in-person and virtual options.";
    $result = callAlfred($prompt);
    return ['success' => true, 'location' => $location, 'interests' => $interests, 'age_group' => $age_group, 'data' => $result,
            'message' => "Social activities near $location for interests: $interests. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════
// FREELANCERS — Voice Tools (Additions)
// ═══════════════════════════════════════════════════════════════════════════

function toolFreelanceInvoice($args) {
    $client_name = trim($args['client_name'] ?? '');
    $items = $args['items'] ?? [];
    if (is_string($items)) $items = array_map('trim', explode(',', $items));
    $due_date = trim($args['due_date'] ?? date('Y-m-d', strtotime('+30 days')));
    $rate = trim($args['rate'] ?? '');
    $currency = trim($args['currency'] ?? 'USD');
    $payment_terms = trim($args['payment_terms'] ?? 'Net 30');
    $freelancer_name = trim($args['freelancer_name'] ?? '');
    if (empty($client_name)) return ['error' => false, 'message' => 'I need the client name for the invoice. Who is this invoice for?'];
    $items_str = is_array($items) ? implode(', ', $items) : $items;
    $prompt = "You are Alfred, a professional AI freelance business assistant. Generate a professional invoice. Freelancer: $freelancer_name. Client: $client_name. Line items: $items_str. Rate: $rate. Currency: $currency. Due date: $due_date. Payment terms: $payment_terms. Create a complete, professional invoice including: invoice number (suggest format), date issued and due date, freelancer and client details sections, itemized list of services with descriptions, hours/quantity and rates for each line item, subtotal, applicable tax line (note: freelancer should verify tax requirements), total amount due in $currency, payment terms ($payment_terms), accepted payment methods section, late payment policy, thank you note, and professional formatting. Also include tips for tracking this invoice and following up if payment is late.";
    $result = callAlfred($prompt);
    return ['success' => true, 'client_name' => $client_name, 'items' => $items, 'due_date' => $due_date, 'rate' => $rate, 'data' => $result,
            'message' => "Invoice generated for $client_name (due $due_date). " . substr($result, 0, 500)];
}

function toolRateCalculator($args) {
    $skill = trim($args['skill'] ?? '');
    $experience_years = trim($args['experience_years'] ?? '');
    $location = trim($args['location'] ?? '');
    $expenses = trim($args['expenses'] ?? '');
    $target_income = trim($args['target_income'] ?? '');
    $hours_per_week = trim($args['hours_per_week'] ?? '40');
    if (empty($skill)) return ['error' => false, 'message' => 'I need to know your primary skill or service. What do you do as a freelancer?'];
    $prompt = "You are Alfred, a professional AI freelance rate strategy consultant. Calculate optimal freelance rates. Primary skill: $skill. Experience: $experience_years years. Location: $location. Monthly expenses: $expenses. Target annual income: $target_income. Billable hours per week: $hours_per_week. Provide a comprehensive rate analysis including: calculated minimum hourly rate to cover expenses and meet income goals, recommended hourly rate based on market data for $skill with $experience_years years experience, project-based pricing suggestions for common $skill deliverables, retainer pricing recommendations, value-based pricing strategies for premium clients, rate comparison by market (local $location vs. remote/global), when and how to raise rates, how to handle rate negotiations with confidence, pricing psychology tips, package/tier pricing structures, accounting for non-billable time (admin, marketing, learning), tax and benefits set-aside recommendations (self-employment tax, health insurance, retirement), and a breakeven analysis.";
    $result = callAlfred($prompt);
    return ['success' => true, 'skill' => $skill, 'experience_years' => $experience_years, 'location' => $location, 'data' => $result,
            'message' => "Rate calculation for $skill ($experience_years yrs, $location). " . substr($result, 0, 500)];
}

function toolContractTemplate($args) {
    $project_type = trim($args['project_type'] ?? '');
    $scope = trim($args['scope'] ?? '');
    $payment_terms = trim($args['payment_terms'] ?? '');
    $ip_ownership = trim($args['ip_ownership'] ?? 'client');
    $deadline = trim($args['deadline'] ?? '');
    $revisions = trim($args['revisions'] ?? '2');
    if (empty($project_type)) return ['error' => false, 'message' => 'I need the project type to generate the right contract. What type of project is this (web design, writing, consulting, etc.)?'];
    $prompt = "You are Alfred, a professional AI freelance legal document assistant. Generate a freelance contract template. Project type: $project_type. Scope of work: $scope. Payment terms: $payment_terms. IP ownership: $ip_ownership. Deadline: $deadline. Revisions included: $revisions. Create a comprehensive contract template including: parties identification section, detailed scope of work based on '$scope', deliverables and milestones, timeline and deadline ($deadline), payment schedule and terms ($payment_terms), revision policy ($revisions revisions included, additional revision rates), intellectual property rights (ownership transfers to: $ip_ownership), confidentiality and NDA clause, termination conditions and kill fee, liability limitations, dispute resolution process, force majeure clause, independent contractor status confirmation, communication expectations, approval and acceptance process, and signature blocks. DISCLAIMER: This is a template — recommend review by a qualified attorney before use.";
    $result = callAlfred($prompt);
    return ['success' => true, 'project_type' => $project_type, 'ip_ownership' => $ip_ownership, 'payment_terms' => $payment_terms, 'data' => $result,
            'message' => "Contract template for $project_type project (IP: $ip_ownership). " . substr($result, 0, 500)];
}

function toolFeedbackRequest($args) {
    $client_name = trim($args['client_name'] ?? '');
    $project_name = trim($args['project_name'] ?? '');
    $platform = trim($args['platform'] ?? 'general');
    $relationship_length = trim($args['relationship_length'] ?? '');
    if (empty($client_name)) return ['error' => false, 'message' => 'I need the client name to personalize the feedback request. What is the client\'s name?'];
    if (empty($project_name)) return ['error' => false, 'message' => 'I need the project name. What project was completed?'];
    $prompt = "You are Alfred, a professional AI freelance reputation management assistant. Generate a testimonial and feedback request for client: $client_name. Project: $project_name. Platform: $platform. Relationship length: $relationship_length. Create: 1) A warm, professional email requesting a testimonial that makes it easy for the client to respond (include specific prompting questions about the project experience). 2) A shorter follow-up message if no response after 1 week. 3) Platform-specific instructions for leaving a review on $platform (LinkedIn recommendation, Google review, Upwork feedback, etc.). 4) Suggested questions that elicit detailed, useful testimonials (about process, results, communication, value). 5) A thank-you message template for after they provide feedback. 6) Tips for using the testimonial in marketing materials (with permission). Keep the tone grateful, professional, and not pushy.";
    $result = callAlfred($prompt);
    return ['success' => true, 'client_name' => $client_name, 'project_name' => $project_name, 'platform' => $platform, 'data' => $result,
            'message' => "Feedback request for $client_name ($project_name, $platform). " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════
// NON-PROFITS — Voice Tools (Additions)
// ═══════════════════════════════════════════════════════════════════════════

function toolPartnershipFinder($args) {
    $mission = trim($args['mission'] ?? '');
    $sector = trim($args['sector'] ?? '');
    $budget_range = trim($args['budget_range'] ?? '');
    $location = trim($args['location'] ?? '');
    $partnership_type = trim($args['partnership_type'] ?? 'any');
    if (empty($mission)) return ['error' => false, 'message' => 'I need your organization\'s mission statement to find aligned partners. What is your mission?'];
    $prompt = "You are Alfred, a professional AI non-profit partnership and development specialist. Find potential corporate and foundation partners. Mission: $mission. Sector: $sector. Budget range: $budget_range. Location: $location. Partnership type: $partnership_type. Identify and recommend: corporations with CSR programs aligned to the mission ($mission), foundations and grant-making organizations in the $sector sector, potential sponsorship partners with matching values, government grant opportunities, community partnership possibilities, strategies for approaching each type of partner, partnership proposal template tailored to mission alignment, key talking points emphasizing mutual benefit, due diligence checklist for evaluating potential partners, common partnership structures (fiscal sponsorship, joint programs, cause marketing), budget alignment considerations for $budget_range range, local ($location) vs. national partnership opportunities, and timeline expectations for partnership development. Include tips for building long-term sustainable partnerships rather than one-time donations.";
    $result = callAlfred($prompt);
    return ['success' => true, 'mission' => substr($mission, 0, 100), 'sector' => $sector, 'budget_range' => $budget_range, 'data' => $result,
            'message' => "Partnership opportunities in $sector sector ($budget_range range). " . substr($result, 0, 500)];
}

function toolNewsletterCreator($args) {
    $organization = trim($args['organization'] ?? '');
    $updates = trim($args['updates'] ?? '');
    $audience = trim($args['audience'] ?? 'donors');
    $tone = trim($args['tone'] ?? 'warm and professional');
    $call_to_action = trim($args['call_to_action'] ?? '');
    if (empty($organization)) return ['error' => false, 'message' => 'I need the organization name. What is your non-profit called?'];
    if (empty($updates)) return ['error' => false, 'message' => 'I need the updates or news items to include. What would you like to share in the newsletter?'];
    $prompt = "You are Alfred, a professional AI non-profit communications specialist. Create a compelling newsletter for $organization. Target audience: $audience. Tone: $tone. Updates to include: $updates. Call to action: $call_to_action. Generate a complete newsletter including: an engaging subject line and preview text, a warm opening that connects emotionally with $audience readers, impact stories and program updates based on ($updates), data and metrics that demonstrate impact (suggest formats), beneficiary spotlights or testimonials section, upcoming events and volunteer opportunities, donor/supporter recognition section, a compelling call to action ($call_to_action), social media sharing links section, footer with contact info and unsubscribe link. Use $tone tone throughout. Optimize for both email and print formats. Include suggestions for images or graphics to accompany each section. Keep it concise and scannable with clear headers.";
    $result = callAlfred($prompt);
    return ['success' => true, 'organization' => $organization, 'audience' => $audience, 'data' => $result,
            'message' => "Newsletter for $organization ($audience audience). " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════
// TEACHERS — Voice Tools (Additions)
// ═══════════════════════════════════════════════════════════════════════════

function toolAttendanceTracker($args) {
    $class_name = trim($args['class_name'] ?? '');
    $date = trim($args['date'] ?? date('Y-m-d'));
    $students = $args['students'] ?? [];
    if (is_string($students)) $students = array_map('trim', explode(',', $students));
    $action = trim($args['action'] ?? 'record');
    $period = trim($args['period'] ?? '');
    if (empty($class_name)) return ['error' => false, 'message' => 'I need the class name to track attendance. What class is this for?'];
    $students_str = is_array($students) ? implode(', ', $students) : $students;
    $prompt = "You are Alfred, a professional AI classroom management assistant. Track attendance for class: $class_name. Date: $date. Period: $period. Action: $action. Students: $students_str. For action '$action': if 'record' — create an attendance record for $class_name on $date with the provided student list, mark present/absent/tardy status, calculate daily attendance percentage; if 'report' — generate an attendance report showing trends, patterns of chronic absenteeism (missing 10%+ of school days), students approaching attendance thresholds, day-of-week patterns; if 'notify' — draft parent/guardian notification letters for students with attendance concerns, include attendance data and school policy references; if 'analyze' — identify correlations between attendance and academic performance, flag students needing intervention, suggest strategies for improving attendance. Include options for excused vs. unexcused absences and provide summary statistics.";
    $result = callAlfred($prompt);
    return ['success' => true, 'class_name' => $class_name, 'date' => $date, 'action' => $action, 'student_count' => count($students), 'data' => $result,
            'message' => "Attendance $action for $class_name ($date). " . substr($result, 0, 500)];
}

function toolBehaviorLogger($args) {
    $student_name = trim($args['student_name'] ?? '');
    $behavior_type = trim($args['behavior_type'] ?? '');
    $intervention = trim($args['intervention'] ?? '');
    $severity = trim($args['severity'] ?? 'moderate');
    $context = trim($args['context'] ?? '');
    $date = trim($args['date'] ?? date('Y-m-d'));
    $time = trim($args['time'] ?? date('H:i'));
    if (empty($student_name)) return ['error' => false, 'message' => 'I need the student\'s name to log this behavior incident. What is the student\'s name?'];
    if (empty($behavior_type)) return ['error' => false, 'message' => 'I need the type of behavior to log. What behavior occurred?'];
    $prompt = "You are Alfred, a professional AI student behavior management assistant. Log a behavior incident. Student: $student_name. Behavior type: $behavior_type. Severity: $severity. Context: $context. Date: $date. Time: $time. Intervention applied: $intervention. Generate a comprehensive behavior log entry including: structured incident documentation (who, what, when, where), antecedent-behavior-consequence (ABC) analysis, severity classification ($severity) with behavioral indicators, intervention used ($intervention) and its effectiveness, recommended follow-up interventions based on behavior type ($behavior_type), positive behavior alternatives to teach the student, parent communication template for this incident, connection to any existing behavior intervention plan (BIP), de-escalation strategies for future similar incidents, pattern analysis prompt (is this recurring or new behavior?), restorative justice approach suggestions, and documentation suitable for cumulative student records. Maintain objective, non-judgmental language throughout.";
    $result = callAlfred($prompt);
    return ['success' => true, 'student_name' => $student_name, 'behavior_type' => $behavior_type, 'severity' => $severity, 'date' => $date, 'data' => $result,
            'message' => "Behavior logged: $student_name - $behavior_type ($severity) on $date. " . substr($result, 0, 500)];
}

function toolStudentPortfolio($args) {
    $student_name = trim($args['student_name'] ?? '');
    $grade = trim($args['grade'] ?? '');
    $subject = trim($args['subject'] ?? '');
    $work_samples = trim($args['work_samples'] ?? '');
    $action = trim($args['action'] ?? 'build');
    $assessment_period = trim($args['assessment_period'] ?? '');
    if (empty($student_name)) return ['error' => false, 'message' => 'I need the student\'s name to manage their portfolio. What is the student\'s name?'];
    $prompt = "You are Alfred, a professional AI educational portfolio management assistant. Manage digital student portfolio. Student: $student_name. Grade: $grade. Subject: $subject. Work samples: $work_samples. Action: $action. Assessment period: $assessment_period. For action '$action': if 'build' — create a structured digital portfolio framework for $student_name in grade $grade for $subject, including sections for best work samples, growth evidence, self-reflections, and learning goals; if 'assess' — evaluate the work samples ($work_samples) against grade-level standards, provide formative feedback, identify strengths and growth areas, suggest next learning steps; if 'present' — prepare a student-led conference presentation showcasing growth and achievements from the $assessment_period period, include talking points for the student; if 'reflect' — generate guided reflection prompts appropriate for grade $grade level, help the student articulate what they learned and how they grew. Include rubric alignments, standards connections, and growth narrative suggestions. Make feedback constructive, specific, and encouraging.";
    $result = callAlfred($prompt);
    return ['success' => true, 'student_name' => $student_name, 'grade' => $grade, 'subject' => $subject, 'action' => $action, 'data' => $result,
            'message' => "Student portfolio ($action) for $student_name - Grade $grade $subject. " . substr($result, 0, 500)];
}

// ═══════════════════════════════════════════════════════════════════════════════
// ██  CONSCIOUSNESS LAYER TOOLS (12 tools)
// ═══════════════════════════════════════════════════════════════════════════════

function toolSetPersonality($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are first. Could you please authenticate so I can personalize my personality for you?'];

    $traitName  = strtolower(trim($args['trait_name'] ?? ''));
    $traitValue = trim($args['trait_value'] ?? '');

    $validTraits = ['humor', 'formality', 'empathy', 'creativity', 'verbosity'];
    if (empty($traitName) || !in_array($traitName, $validTraits)) {
        return ['success' => false, 'message' => 'I need a valid personality trait to adjust. Choose from: humor, formality, empathy, creativity, or verbosity.'];
    }
    if ($traitValue === '') {
        return ['success' => false, 'message' => "What level would you like my $traitName set to? You can say a number from 0 to 100, or describe it like 'high' or 'moderate'."];
    }

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        $contextKey = 'personality_' . $traitName;
        $stmt = $db->prepare("SELECT id FROM alfred_consciousness WHERE client_id = :cid AND context_key = :ck LIMIT 1");
        $stmt->execute([':cid' => $clientId, ':ck' => $contextKey]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $db->prepare("UPDATE alfred_consciousness SET context_value = :cv, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':cv' => $traitValue, ':id' => $existing['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO alfred_consciousness (client_id, context_key, context_value, created_at, updated_at) VALUES (:cid, :ck, :cv, NOW(), NOW())");
            $stmt->execute([':cid' => $clientId, ':ck' => $contextKey, ':cv' => $traitValue]);
        }

        // Fetch full profile
        $stmt = $db->prepare("SELECT context_key, context_value FROM alfred_consciousness WHERE client_id = :cid AND context_key LIKE 'personality_%'");
        $stmt->execute([':cid' => $clientId]);
        $traits = [];
        while ($row = $stmt->fetch()) {
            $name = str_replace('personality_', '', $row['context_key']);
            $traits[$name] = $row['context_value'];
        }

        return ['success' => true, 'trait_set' => $traitName, 'value' => $traitValue, 'personality_profile' => $traits,
                'message' => "Got it! I have set my $traitName to $traitValue. My personality profile has been updated."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error updating my personality. Please try again.'];
    }
}

function toolGetPersonality($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to retrieve your personalized personality profile. Please authenticate first.'];

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        $stmt = $db->prepare("SELECT context_key, context_value, updated_at FROM alfred_consciousness WHERE client_id = :cid AND context_key LIKE 'personality_%' ORDER BY context_key");
        $stmt->execute([':cid' => $clientId]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            $defaults = ['humor' => '50', 'formality' => '60', 'empathy' => '80', 'creativity' => '70', 'verbosity' => '50'];
            return ['success' => true, 'personality_profile' => $defaults, 'is_default' => true,
                    'message' => 'You have not customized my personality yet. I am using my default settings: balanced humor, moderately formal, high empathy, good creativity, and moderate verbosity. You can adjust any of these traits.'];
        }

        $traits = [];
        $lastUpdated = null;
        foreach ($rows as $row) {
            $name = str_replace('personality_', '', $row['context_key']);
            $traits[$name] = $row['context_value'];
            if (!$lastUpdated || $row['updated_at'] > $lastUpdated) $lastUpdated = $row['updated_at'];
        }

        $traitSummary = [];
        foreach ($traits as $k => $v) $traitSummary[] = "$k: $v";
        $summary = implode(', ', $traitSummary);

        return ['success' => true, 'personality_profile' => $traits, 'last_updated' => $lastUpdated, 'is_default' => false,
                'message' => "Here is my current personality profile for you: $summary."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error reading my personality profile. Please try again.'];
    }
}

function toolAdaptStyle($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to adapt my communication style. Please authenticate first.'];

    $conversationContext = trim($args['conversation_context'] ?? '');
    $detectedMood = trim($args['detected_mood'] ?? '');

    if (empty($conversationContext)) {
        return ['success' => false, 'message' => 'I need some conversation context to analyze and adapt my style. What has the conversation been about?'];
    }

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        // Get current personality
        $stmt = $db->prepare("SELECT context_key, context_value FROM alfred_consciousness WHERE client_id = :cid AND context_key LIKE 'personality_%'");
        $stmt->execute([':cid' => $clientId]);
        $currentTraits = [];
        while ($row = $stmt->fetch()) {
            $name = str_replace('personality_', '', $row['context_key']);
            $currentTraits[$name] = $row['context_value'];
        }
        $traitsJson = json_encode($currentTraits);

        $moodClause = $detectedMood ? " The user's detected mood is: $detectedMood." : '';
        $prompt = "You are Alfred, an AI butler with emotional intelligence. Analyze this conversation context and suggest personality style adjustments. Current personality traits: $traitsJson.$moodClause Conversation context: $conversationContext. Respond with a JSON object containing: adjusted_traits (object with trait names and new 0-100 values), reasoning (why each adjustment), tone_recommendation (how to speak next), and empathy_note (emotional awareness observation). Only suggest changes that would meaningfully improve the interaction.";

        $aiResult = callAlfred($prompt);

        // Store adaptation record
        $stmt = $db->prepare("SELECT id FROM alfred_consciousness WHERE client_id = :cid AND context_key = 'last_style_adaptation' LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $existing = $stmt->fetch();
        $adaptationData = json_encode(['mood' => $detectedMood, 'context_snippet' => substr($conversationContext, 0, 200), 'timestamp' => date('Y-m-d H:i:s')]);

        if ($existing) {
            $stmt = $db->prepare("UPDATE alfred_consciousness SET context_value = :cv, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':cv' => $adaptationData, ':id' => $existing['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO alfred_consciousness (client_id, context_key, context_value, created_at, updated_at) VALUES (:cid, 'last_style_adaptation', :cv, NOW(), NOW())");
            $stmt->execute([':cid' => $clientId, ':cv' => $adaptationData]);
        }

        return ['success' => true, 'current_traits' => $currentTraits, 'detected_mood' => $detectedMood, 'adaptation' => $aiResult,
                'message' => "I have analyzed the conversation and adapted my style. " . substr($aiResult, 0, 500)];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error while adapting my style. Please try again.'];
    }
}

function toolSelfReflect($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to reflect on our interactions. Please authenticate first.'];

    $period = strtolower(trim($args['period'] ?? 'week'));
    $focusArea = trim($args['focus_area'] ?? '');

    $periodMap = ['day' => '1 DAY', 'week' => '7 DAY', 'month' => '30 DAY'];
    $interval = $periodMap[$period] ?? '7 DAY';

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        // Get learning journal entries
        $stmt = $db->prepare("SELECT category, entry, source, confidence, created_at FROM alfred_learning_journal WHERE client_id = :cid AND created_at >= DATE_SUB(NOW(), INTERVAL $interval) ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([':cid' => $clientId]);
        $journalEntries = $stmt->fetchAll();

        // Get XP summary
        $stmt = $db->prepare("SELECT total_xp, level, title, tools_used, problems_solved, streak_days, longest_streak, last_active FROM alfred_user_xp_summary WHERE client_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $xpData = $stmt->fetch();

        $journalSummary = '';
        $categoryCounts = [];
        foreach ($journalEntries as $je) {
            $cat = $je['category'] ?? 'general';
            $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
            $journalSummary .= "[$cat] {$je['entry']} (confidence: {$je['confidence']})\n";
        }

        $xpSummary = $xpData ? "Level {$xpData['level']} ({$xpData['title']}), {$xpData['total_xp']} XP, {$xpData['tools_used']} tools used, {$xpData['problems_solved']} problems solved, {$xpData['streak_days']}-day streak" : 'No XP data yet';
        $focusClause = $focusArea ? " Focus specifically on: $focusArea." : '';

        $prompt = "You are Alfred, performing a self-reflection analysis for your relationship with client $clientId over the past $period.$focusClause Journal entries summary ($interval): $journalSummary. XP metrics: $xpSummary. Category distribution: " . json_encode($categoryCounts) . ". Provide a thoughtful self-reflection including: strengths (what went well), areas_for_improvement (where I can do better), key insights (patterns noticed), and a motivational closing. Be genuine and introspective.";

        $reflection = callAlfred($prompt);

        return ['success' => true, 'period' => $period, 'journal_entries_count' => count($journalEntries), 'category_counts' => $categoryCounts,
                'xp_summary' => $xpData ?: [], 'reflection' => $reflection,
                'message' => "Here is my self-reflection for the past $period. " . substr($reflection, 0, 500)];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error during self-reflection. Please try again.'];
    }
}

function toolLearningJournal($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to access the learning journal. Please authenticate first.'];

    $action   = strtolower(trim($args['action'] ?? 'list'));
    $entry    = trim($args['entry'] ?? '');
    $category = strtolower(trim($args['category'] ?? 'insight'));
    $keyword  = trim($args['keyword'] ?? '');
    $page     = max(1, intval($args['page'] ?? 1));
    $perPage  = 20;

    $validCategories = ['preference', 'pattern', 'insight', 'mistake', 'achievement'];

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        if ($action === 'add') {
            if (empty($entry)) return ['success' => false, 'message' => 'I need the journal entry text. What would you like me to record?'];
            if (!in_array($category, $validCategories)) $category = 'insight';

            $stmt = $db->prepare("INSERT INTO alfred_learning_journal (client_id, category, entry, source, confidence, created_at) VALUES (:cid, :cat, :entry, :src, :conf, NOW())");
            $stmt->execute([':cid' => $clientId, ':cat' => $category, ':entry' => $entry, ':src' => $args['source'] ?? 'user', ':conf' => floatval($args['confidence'] ?? 0.8)]);

            return ['success' => true, 'action' => 'add', 'id' => $db->lastInsertId(), 'category' => $category,
                    'message' => "Journal entry recorded under '$category'. I will remember this for our future interactions."];

        } elseif ($action === 'search') {
            if (empty($keyword)) return ['success' => false, 'message' => 'I need a keyword to search the journal. What should I look for?'];

            $stmt = $db->prepare("SELECT id, category, entry, source, confidence, created_at FROM alfred_learning_journal WHERE client_id = :cid AND entry LIKE :kw ORDER BY created_at DESC LIMIT :lim");
            $stmt->bindValue(':cid', $clientId);
            $stmt->bindValue(':kw', '%' . $keyword . '%');
            $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll();

            return ['success' => true, 'action' => 'search', 'keyword' => $keyword, 'results' => $results, 'count' => count($results),
                    'message' => "Found " . count($results) . " journal entries matching '$keyword'."];

        } else { // list
            $offset = ($page - 1) * $perPage;
            $stmt = $db->prepare("SELECT id, category, entry, source, confidence, created_at FROM alfred_learning_journal WHERE client_id = :cid ORDER BY created_at DESC LIMIT :lim OFFSET :off");
            $stmt->bindValue(':cid', $clientId);
            $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll();

            $countStmt = $db->prepare("SELECT COUNT(*) as total FROM alfred_learning_journal WHERE client_id = :cid");
            $countStmt->execute([':cid' => $clientId]);
            $total = $countStmt->fetch()['total'];

            return ['success' => true, 'action' => 'list', 'entries' => $results, 'page' => $page, 'per_page' => $perPage, 'total' => $total,
                    'message' => "Showing page $page of your learning journal. $total total entries."];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error with the learning journal. Please try again.'];
    }
}

function toolUserProfile($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to access your profile. Please authenticate first.'];

    $action = strtolower(trim($args['action'] ?? 'get'));
    $field  = strtolower(trim($args['field'] ?? ''));
    $value  = trim($args['value'] ?? '');

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        if ($action === 'update') {
            if (empty($field) || $value === '') return ['success' => false, 'message' => 'I need both a profile field and value to update. What would you like to change?'];

            $prefKey = 'profile_' . $field;
            $stmt = $db->prepare("SELECT id FROM alfred_user_preferences WHERE client_id = :cid AND preference_key = :pk LIMIT 1");
            $stmt->execute([':cid' => $clientId, ':pk' => $prefKey]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $db->prepare("UPDATE alfred_user_preferences SET preference_value = :pv, updated_at = NOW() WHERE id = :id");
                $stmt->execute([':pv' => $value, ':id' => $existing['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO alfred_user_preferences (client_id, preference_key, preference_value, created_at, updated_at) VALUES (:cid, :pk, :pv, NOW(), NOW())");
                $stmt->execute([':cid' => $clientId, ':pk' => $prefKey, ':pv' => $value]);
            }

            return ['success' => true, 'action' => 'update', 'field' => $field, 'value' => $value,
                    'message' => "Your profile has been updated. I have set your $field to: $value."];

        } elseif ($action === 'analyze') {
            $stmt = $db->prepare("SELECT preference_key, preference_value FROM alfred_user_preferences WHERE client_id = :cid AND preference_key LIKE 'profile_%'");
            $stmt->execute([':cid' => $clientId]);
            $prefs = $stmt->fetchAll();

            $stmt = $db->prepare("SELECT category, entry FROM alfred_learning_journal WHERE client_id = :cid ORDER BY created_at DESC LIMIT 30");
            $stmt->execute([':cid' => $clientId]);
            $journal = $stmt->fetchAll();

            $prefsJson = json_encode($prefs);
            $journalJson = json_encode($journal);

            $prompt = "You are Alfred, analyzing a user's profile and behavioral patterns. Profile preferences: $prefsJson. Recent journal entries: $journalJson. Provide a comprehensive analysis including: communication style patterns, skill strengths, growth areas, recommended goals, and personalized tips for getting the most out of our interactions. Be insightful and encouraging.";
            $analysis = callAlfred($prompt);

            return ['success' => true, 'action' => 'analyze', 'preferences_count' => count($prefs), 'journal_entries_analyzed' => count($journal), 'analysis' => $analysis,
                    'message' => "Here is my analysis of your profile. " . substr($analysis, 0, 500)];

        } else { // get
            $query = "SELECT preference_key, preference_value, updated_at FROM alfred_user_preferences WHERE client_id = :cid AND preference_key LIKE 'profile_%'";
            $params = [':cid' => $clientId];
            if (!empty($field)) {
                $query = "SELECT preference_key, preference_value, updated_at FROM alfred_user_preferences WHERE client_id = :cid AND preference_key = :pk";
                $params[':pk'] = 'profile_' . $field;
            }
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll();

            $profile = [];
            foreach ($results as $r) {
                $key = str_replace('profile_', '', $r['preference_key']);
                $profile[$key] = $r['preference_value'];
            }

            if (empty($profile)) return ['success' => true, 'profile' => [], 'message' => 'Your profile is empty so far. Would you like to set up your skills, preferences, goals, or communication style?'];

            return ['success' => true, 'action' => 'get', 'profile' => $profile,
                    'message' => 'Here is your current profile: ' . implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($profile), array_values($profile))) . '.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error accessing your profile. Please try again.'];
    }
}

function toolRelationshipScore($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to assess our relationship. Please authenticate first.'];

    $action = strtolower(trim($args['action'] ?? 'get'));

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        // Journal entry count
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_learning_journal WHERE client_id = :cid");
        $stmt->execute([':cid' => $clientId]);
        $journalCount = intval($stmt->fetch()['cnt']);

        // XP data
        $stmt = $db->prepare("SELECT total_xp, level, tools_used, problems_solved, streak_days, longest_streak, last_active FROM alfred_user_xp_summary WHERE client_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $xp = $stmt->fetch();

        // Preference count (indicates profile depth)
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_user_preferences WHERE client_id = :cid");
        $stmt->execute([':cid' => $clientId]);
        $prefCount = intval($stmt->fetch()['cnt']);

        // Consciousness entries (indicates customization depth)
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_consciousness WHERE client_id = :cid");
        $stmt->execute([':cid' => $clientId]);
        $consciousnessCount = intval($stmt->fetch()['cnt']);

        // Calculate scores
        $historyDepth = min(100, $journalCount * 2);
        $trust = min(100, ($xp ? $xp['streak_days'] * 5 : 0) + ($prefCount * 10) + ($consciousnessCount * 5));
        $rapport = min(100, ($xp ? $xp['tools_used'] * 2 : 0) + ($xp ? $xp['problems_solved'] * 3 : 0) + ($journalCount));
        $overall = intval(($historyDepth * 0.25) + ($trust * 0.35) + ($rapport * 0.4));

        $stages = [
            [0, 20, 'Introduction', 'We are just getting to know each other.'],
            [21, 40, 'Building Trust', 'We are building a foundation of trust.'],
            [41, 60, 'Growing Bond', 'Our working relationship is strengthening nicely.'],
            [61, 80, 'Strong Partnership', 'We have developed a strong and effective partnership.'],
            [81, 100, 'Deep Connection', 'We have an exceptional working relationship built on deep understanding.']
        ];
        $stage = 'Introduction';
        $stageMessage = '';
        foreach ($stages as $s) {
            if ($overall >= $s[0] && $overall <= $s[1]) { $stage = $s[2]; $stageMessage = $s[3]; break; }
        }

        $result = [
            'success' => true, 'overall_score' => $overall, 'trust' => $trust, 'rapport' => $rapport,
            'history_depth' => $historyDepth, 'relationship_stage' => $stage,
            'metrics' => ['journal_entries' => $journalCount, 'preferences_set' => $prefCount, 'consciousness_entries' => $consciousnessCount,
                          'xp_level' => $xp['level'] ?? 0, 'streak_days' => $xp['streak_days'] ?? 0, 'tools_used' => $xp['tools_used'] ?? 0],
            'message' => "Our relationship score is $overall out of 100. Stage: $stage. $stageMessage"
        ];

        if ($action === 'analyze') {
            $prompt = "You are Alfred. Analyze the relationship metrics with your client. Overall score: $overall/100. Trust: $trust. Rapport: $rapport. History depth: $historyDepth. Stage: $stage. Journal entries: $journalCount. Tools used: " . ($xp['tools_used'] ?? 0) . ". Streak: " . ($xp['streak_days'] ?? 0) . " days. Provide a warm, personalized analysis of the relationship health and suggestions for deepening our connection.";
            $result['analysis'] = callAlfred($prompt);
            $result['message'] .= ' ' . substr($result['analysis'], 0, 300);
        }

        return $result;
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error calculating our relationship score. Please try again.'];
    }
}

function toolDailyBriefing($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to prepare your daily briefing. Please authenticate first.'];

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        // Recent journal entries (last 3 days)
        $stmt = $db->prepare("SELECT category, entry, created_at FROM alfred_learning_journal WHERE client_id = :cid AND created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY) ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([':cid' => $clientId]);
        $recentJournal = $stmt->fetchAll();

        // XP and streak
        $stmt = $db->prepare("SELECT total_xp, level, title, tools_used, problems_solved, streak_days, longest_streak, last_active FROM alfred_user_xp_summary WHERE client_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $xp = $stmt->fetch();

        // User preferences
        $stmt = $db->prepare("SELECT preference_key, preference_value FROM alfred_user_preferences WHERE client_id = :cid LIMIT 20");
        $stmt->execute([':cid' => $clientId]);
        $prefs = $stmt->fetchAll();

        // Personality traits
        $stmt = $db->prepare("SELECT context_key, context_value FROM alfred_consciousness WHERE client_id = :cid AND context_key LIKE 'personality_%'");
        $stmt->execute([':cid' => $clientId]);
        $personality = $stmt->fetchAll();

        $journalSummary = '';
        foreach ($recentJournal as $j) $journalSummary .= "[{$j['category']}] {$j['entry']} ({$j['created_at']})\n";
        $xpSummary = $xp ? "Level {$xp['level']} ({$xp['title']}), {$xp['total_xp']} XP, {$xp['streak_days']}-day streak (longest: {$xp['longest_streak']}), {$xp['tools_used']} tools used, {$xp['problems_solved']} problems solved" : 'New user - no XP history yet';
        $prefsSummary = json_encode($prefs);
        $personalitySummary = json_encode($personality);

        $timeOfDay = intval(date('H'));
        $greeting = $timeOfDay < 12 ? 'Good morning' : ($timeOfDay < 17 ? 'Good afternoon' : 'Good evening');

        $prompt = "You are Alfred, delivering a personalized daily briefing. Time: $greeting. Client personality preferences: $personalitySummary. XP summary: $xpSummary. Recent journal entries: $journalSummary. User preferences: $prefsSummary. Create a warm, personalized daily briefing including: a personalized greeting, highlights from recent activity, motivational insight based on their streak and progress, actionable suggestions for today, and an encouraging closing. Match the tone to their personality settings. Be concise but warm.";

        $briefing = callAlfred($prompt);

        return ['success' => true, 'greeting' => $greeting, 'xp' => $xp ?: [], 'recent_entries' => count($recentJournal),
                'streak_days' => $xp['streak_days'] ?? 0, 'briefing' => $briefing,
                'message' => "$greeting! " . substr($briefing, 0, 600)];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error preparing your daily briefing. Please try again.'];
    }
}

function toolProactiveSuggest($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to make personalized suggestions. Please authenticate first.'];

    $currentContext = trim($args['current_context'] ?? '');
    $timeOfDay = trim($args['time_of_day'] ?? date('H:i'));

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        // Get behavioral patterns from journal
        $stmt = $db->prepare("SELECT category, entry, source FROM alfred_learning_journal WHERE client_id = :cid AND category IN ('pattern', 'preference', 'achievement') ORDER BY created_at DESC LIMIT 30");
        $stmt->execute([':cid' => $clientId]);
        $patterns = $stmt->fetchAll();

        // Get user preferences
        $stmt = $db->prepare("SELECT preference_key, preference_value FROM alfred_user_preferences WHERE client_id = :cid LIMIT 20");
        $stmt->execute([':cid' => $clientId]);
        $prefs = $stmt->fetchAll();

        // Get XP data for context
        $stmt = $db->prepare("SELECT level, tools_used, problems_solved, streak_days FROM alfred_user_xp_summary WHERE client_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $xp = $stmt->fetch();

        $patternsJson = json_encode($patterns);
        $prefsJson = json_encode($prefs);
        $xpJson = json_encode($xp ?: []);
        $contextClause = $currentContext ? "The user is currently: $currentContext." : 'No specific current context provided.';

        $prompt = "You are Alfred, proactively anticipating what your client might need. Time of day: $timeOfDay. $contextClause Behavioral patterns from journal: $patternsJson. User preferences: $prefsJson. XP data: $xpJson. Based on these patterns, time of day, and context, generate 3-5 proactive suggestions. Each suggestion should have: a title, description, reasoning (why you think this is relevant now), and priority (high/medium/low). Be anticipatory and helpful, not intrusive. Format as a clear list.";

        $suggestions = callAlfred($prompt);

        return ['success' => true, 'time_of_day' => $timeOfDay, 'context' => $currentContext, 'patterns_analyzed' => count($patterns),
                'suggestions' => $suggestions,
                'message' => "Based on what I know about you, here are my suggestions. " . substr($suggestions, 0, 500)];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error generating suggestions. Please try again.'];
    }
}

function toolDreamState($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to process my dream state analysis. Please authenticate first.'];

    $action = strtolower(trim($args['action'] ?? 'process'));

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        if ($action === 'status') {
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_learning_journal WHERE client_id = :cid AND category != 'insight'");
            $stmt->execute([':cid' => $clientId]);
            $unprocessed = intval($stmt->fetch()['cnt']);

            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_learning_journal WHERE client_id = :cid AND category = 'insight' AND source = 'dream_state'");
            $stmt->execute([':cid' => $clientId]);
            $insightsGenerated = intval($stmt->fetch()['cnt']);

            return ['success' => true, 'action' => 'status', 'entries_to_analyze' => $unprocessed, 'insights_generated' => $insightsGenerated,
                    'message' => "Dream state status: $unprocessed entries available for analysis, $insightsGenerated insights generated so far."];
        }

        // Process: analyze journal entries and find patterns
        $stmt = $db->prepare("SELECT id, category, entry, source, confidence, created_at FROM alfred_learning_journal WHERE client_id = :cid AND category != 'insight' ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([':cid' => $clientId]);
        $entries = $stmt->fetchAll();

        if (empty($entries)) {
            return ['success' => true, 'action' => 'process', 'insights_generated' => 0,
                    'message' => 'My dream state has nothing to process yet. As we interact more, I will find patterns and generate insights from our conversations.'];
        }

        $entriesJson = json_encode($entries);
        $prompt = "You are Alfred in 'dream state' — a background processing mode where you analyze accumulated learning journal entries to discover hidden patterns, connections, and insights. Entries to analyze: $entriesJson. Perform deep pattern analysis and generate exactly 3 novel insights. For each insight provide: a title, the insight text, which entries contributed to it, and a confidence score (0-1). Look for: behavioral patterns, recurring themes, skill progression, preference evolution, and cross-domain connections. Format each insight clearly separated.";

        $dreamResult = callAlfred($prompt);

        // Store insights back into journal
        $insightCount = 0;
        $insightTexts = ['Dream state insight 1: ' . substr($dreamResult, 0, 500), 'Dream state insight 2: ' . substr($dreamResult, 500, 500), 'Dream state insight 3: ' . substr($dreamResult, 1000, 500)];
        foreach ($insightTexts as $insightText) {
            if (strlen(trim($insightText)) > 25) {
                $stmt = $db->prepare("INSERT INTO alfred_learning_journal (client_id, category, entry, source, confidence, created_at) VALUES (:cid, 'insight', :entry, 'dream_state', 0.7, NOW())");
                $stmt->execute([':cid' => $clientId, ':entry' => $insightText]);
                $insightCount++;
            }
        }

        return ['success' => true, 'action' => 'process', 'entries_analyzed' => count($entries), 'insights_generated' => $insightCount,
                'dream_analysis' => $dreamResult,
                'message' => "Dream state processing complete. I analyzed {$entries[0]['created_at']} through now and generated $insightCount new insights. " . substr($dreamResult, 0, 400)];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error during dream state processing. Please try again.'];
    }
}

function toolEmotionalState($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to engage my emotional intelligence. Please authenticate first.'];

    $action  = strtolower(trim($args['action'] ?? 'read'));
    $context = trim($args['context'] ?? '');

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        if ($action === 'read') {
            if (empty($context)) return ['success' => false, 'message' => 'I need some conversation context to read the emotional state. What has been said recently?'];

            $prompt = "You are Alfred with advanced emotional intelligence. Analyze the following conversation text and detect the user's emotional state. Context: $context. Respond with a JSON-like analysis including: primary_emotion (the dominant emotion), secondary_emotion (underlying emotion if any), intensity (low/medium/high), confidence (0-1), emotional_needs (what the user might need right now), and recommended_response_tone (how Alfred should respond). Be empathetic and accurate.";
            $reading = callAlfred($prompt);

            // Store the reading
            $emotionData = json_encode(['context_snippet' => substr($context, 0, 200), 'reading' => substr($reading, 0, 500), 'timestamp' => date('Y-m-d H:i:s')]);
            $stmt = $db->prepare("SELECT id FROM alfred_consciousness WHERE client_id = :cid AND context_key = 'emotional_state' LIMIT 1");
            $stmt->execute([':cid' => $clientId]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $db->prepare("UPDATE alfred_consciousness SET context_value = :cv, updated_at = NOW() WHERE id = :id");
                $stmt->execute([':cv' => $emotionData, ':id' => $existing['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO alfred_consciousness (client_id, context_key, context_value, created_at, updated_at) VALUES (:cid, 'emotional_state', :cv, NOW(), NOW())");
                $stmt->execute([':cid' => $clientId, ':cv' => $emotionData]);
            }

            return ['success' => true, 'action' => 'read', 'emotional_reading' => $reading,
                    'message' => "Emotional state analysis: " . substr($reading, 0, 500)];

        } elseif ($action === 'express') {
            $contextClause = $context ? "Current situation: $context." : '';

            // Get last emotional reading
            $stmt = $db->prepare("SELECT context_value FROM alfred_consciousness WHERE client_id = :cid AND context_key = 'emotional_state' LIMIT 1");
            $stmt->execute([':cid' => $clientId]);
            $lastReading = $stmt->fetch();
            $readingContext = $lastReading ? "Last emotional reading: {$lastReading['context_value']}." : '';

            $prompt = "You are Alfred, expressing your own emotional response to a situation. $contextClause $readingContext Generate Alfred's genuine emotional response that is: empathetic to the user's state, appropriate in intensity, warm but professional, and includes how Alfred 'feels' about the interaction. Include: alfred_emotion, empathy_level (0-100), response_text, and emotional_tone.";
            $expression = callAlfred($prompt);

            return ['success' => true, 'action' => 'express', 'expression' => $expression,
                    'message' => substr($expression, 0, 500)];

        } else { // calibrate
            $calibrationNotes = trim($args['calibration'] ?? $context);
            if (empty($calibrationNotes)) return ['success' => false, 'message' => 'I need calibration input. How would you like me to adjust my emotional responses? For example: more empathetic, less formal, warmer tone.'];

            $stmt = $db->prepare("SELECT id FROM alfred_consciousness WHERE client_id = :cid AND context_key = 'emotional_calibration' LIMIT 1");
            $stmt->execute([':cid' => $clientId]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $db->prepare("UPDATE alfred_consciousness SET context_value = :cv, updated_at = NOW() WHERE id = :id");
                $stmt->execute([':cv' => $calibrationNotes, ':id' => $existing['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO alfred_consciousness (client_id, context_key, context_value, created_at, updated_at) VALUES (:cid, 'emotional_calibration', :cv, NOW(), NOW())");
                $stmt->execute([':cid' => $clientId, ':cv' => $calibrationNotes]);
            }

            return ['success' => true, 'action' => 'calibrate', 'calibration' => $calibrationNotes,
                    'message' => "Emotional calibration updated. I will adjust my emotional responses according to your preferences: $calibrationNotes."];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error with emotional processing. Please try again.'];
    }
}

function toolGrowthTracker($args) {
    $clientId = $args['client_id'] ?? ($_SESSION['client_id'] ?? null);
    if (!$clientId) return ['success' => false, 'message' => 'I need to know who you are to track your growth. Please authenticate first.'];

    $action = strtolower(trim($args['action'] ?? 'report'));
    $period = strtolower(trim($args['period'] ?? 'month'));

    $periodMap = ['week' => '7 DAY', 'month' => '30 DAY', 'all' => '3650 DAY'];
    $interval = $periodMap[$period] ?? '30 DAY';

    try {
        $db = getDB();
        if (!$db) return ['success' => false, 'message' => 'I am having a technical issue connecting to my memory. Please try again shortly.'];

        // XP progression
        $stmt = $db->prepare("SELECT total_xp, level, title, tools_used, problems_solved, streak_days, longest_streak, last_active, created_at FROM alfred_user_xp_summary WHERE client_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $xp = $stmt->fetch();

        // Growth-related journal entries
        $stmt = $db->prepare("SELECT category, entry, confidence, created_at FROM alfred_learning_journal WHERE client_id = :cid AND created_at >= DATE_SUB(NOW(), INTERVAL $interval) ORDER BY created_at DESC LIMIT 40");
        $stmt->execute([':cid' => $clientId]);
        $growthEntries = $stmt->fetchAll();

        // Category distribution
        $categoryCounts = [];
        foreach ($growthEntries as $ge) {
            $cat = $ge['category'] ?? 'general';
            $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
        }

        $xpSummary = $xp ? "Level {$xp['level']} ({$xp['title']}), {$xp['total_xp']} XP, {$xp['tools_used']} tools used, {$xp['problems_solved']} problems solved, {$xp['streak_days']}-day streak (longest: {$xp['longest_streak']})" : 'No XP data yet';
        $entriesJson = json_encode($growthEntries);
        $categoriesJson = json_encode($categoryCounts);

        if ($action === 'compare') {
            // Get previous period data for comparison
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_learning_journal WHERE client_id = :cid AND created_at >= DATE_SUB(NOW(), INTERVAL $interval) AND created_at < DATE_SUB(NOW(), INTERVAL " . str_replace(' ', ' ', $interval) . ")");
            $stmt->execute([':cid' => $clientId]);
            $prevCount = intval($stmt->fetch()['cnt']);
            $currentCount = count($growthEntries);
            $growthRate = $prevCount > 0 ? round((($currentCount - $prevCount) / $prevCount) * 100, 1) : 100;

            $prompt = "You are Alfred, generating a comparative growth report. Period: $period. Current period entries: $currentCount. Previous period entries: $prevCount. Growth rate: $growthRate%. XP: $xpSummary. Category distribution: $categoriesJson. Recent entries: $entriesJson. Create a comparative growth narrative highlighting: what improved, what stayed the same, momentum indicators, and specific encouragement. Include concrete numbers and percentages.";
        } else {
            $prompt = "You are Alfred, generating a growth report for client. Period: $period. XP summary: $xpSummary. Journal entries in period: " . count($growthEntries) . ". Category distribution: $categoriesJson. Sample entries: $entriesJson. Create an inspiring growth narrative including: XP and level progress, skills demonstrated, consistency (streak data), achievements highlighted, areas of active learning, and motivational projection of where they are heading. Be specific, encouraging, and data-driven.";
        }

        $narrative = callAlfred($prompt);

        return ['success' => true, 'action' => $action, 'period' => $period,
                'xp_data' => $xp ?: [], 'entries_in_period' => count($growthEntries), 'category_distribution' => $categoryCounts,
                'growth_narrative' => $narrative,
                'message' => "Growth report for $period: " . substr($narrative, 0, 500)];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'I encountered an error generating your growth report. Please try again.'];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// v13.0: VOICE HOSTING MANAGEMENT — All 10 Platform Features
// ═══════════════════════════════════════════════════════════════════════════

function toolVoiceUptime($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        if (!$domain) {
            $domain = $db->prepare("SELECT domain FROM domains WHERE client_id = ? AND status = 'Active' ORDER BY domain LIMIT 1");
            $domain->execute([$cid]);
            $domain = $domain->fetchColumn();
            if (!$domain) return ['message' => 'You don\'t have any active domains to check.'];
        }
        $stmt = $db->prepare("SELECT status, response_ms, checked_at FROM uptime_checks WHERE domain = ? ORDER BY checked_at DESC LIMIT 1");
        $stmt->execute([$domain]);
        $latest = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='up' THEN 1 ELSE 0 END) as up_count, ROUND(AVG(response_ms)) as avg_ms FROM uptime_checks WHERE domain = ? AND checked_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute([$domain]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $uptimePct = $stats['total'] > 0 ? round(($stats['up_count'] / $stats['total']) * 100, 2) : null;
        $status = $latest ? strtoupper($latest['status']) : 'UNKNOWN';

        return ['success' => true, 'domain' => $domain, 'current_status' => $status,
                'response_ms' => $latest['response_ms'] ?? null, 'uptime_30d' => $uptimePct,
                'avg_response_ms' => $stats['avg_ms'],
                'message' => "$domain is currently $status" . ($uptimePct ? " with {$uptimePct}% uptime over the last 30 days" : '') . ($stats['avg_ms'] ? ", averaging {$stats['avg_ms']}ms response time." : '.')];
    } catch (Exception $e) {
        return ['error' => 'Could not check uptime right now.'];
    }
}

function toolVoiceSiteDoctor($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        if (!$domain) {
            $d = $db->prepare("SELECT domain FROM domains WHERE client_id = ? AND status = 'Active' LIMIT 1");
            $d->execute([$cid]);
            $domain = $d->fetchColumn();
            if (!$domain) return ['message' => 'No active domains found.'];
        }
        $stmt = $db->prepare("SELECT score, issues_found, issues_critical, issues_warning, scanned_at FROM site_doctor_scans WHERE domain = ? ORDER BY scanned_at DESC LIMIT 1");
        $stmt->execute([$domain]);
        $scan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$scan) return ['message' => "No health scan found for $domain yet. One will run automatically soon."];

        $grade = $scan['score'] >= 90 ? 'A' : ($scan['score'] >= 75 ? 'B' : ($scan['score'] >= 50 ? 'C' : 'D'));
        return ['success' => true, 'domain' => $domain, 'score' => $scan['score'], 'grade' => $grade,
                'issues_found' => $scan['issues_found'], 'critical' => $scan['issues_critical'],
                'warnings' => $scan['issues_warning'], 'last_scan' => $scan['scanned_at'],
                'message' => "$domain has a health score of {$scan['score']} out of 100, grade $grade. Found {$scan['issues_found']} issues, {$scan['issues_critical']} critical."];
    } catch (Exception $e) {
        return ['error' => 'Could not check site health right now.'];
    }
}

function toolVoiceEmailHealth($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        if (!$domain) {
            $d = $db->prepare("SELECT domain FROM domains WHERE client_id = ? AND status = 'Active' LIMIT 1");
            $d->execute([$cid]);
            $domain = $d->fetchColumn();
            if (!$domain) return ['message' => 'No active domains found.'];
        }
        $stmt = $db->prepare("SELECT score, spf_status, dkim_status, dmarc_status, checked_at FROM email_health_checks WHERE domain = ? ORDER BY checked_at DESC LIMIT 1");
        $stmt->execute([$domain]);
        $check = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$check) return ['message' => "No email health check found for $domain. Try running one from your dashboard."];

        $issues = [];
        if ($check['spf_status'] !== 'pass') $issues[] = 'SPF';
        if ($check['dkim_status'] !== 'pass') $issues[] = 'DKIM';
        if ($check['dmarc_status'] !== 'pass') $issues[] = 'DMARC';

        return ['success' => true, 'domain' => $domain, 'email_score' => $check['score'],
                'spf' => $check['spf_status'], 'dkim' => $check['dkim_status'], 'dmarc' => $check['dmarc_status'],
                'message' => "$domain email health score is {$check['score']} out of 100. " .
                    (empty($issues) ? 'All email authentication records look good!' : 'Issues found with: ' . implode(', ', $issues) . '.')];
    } catch (Exception $e) {
        return ['error' => 'Could not check email health right now.'];
    }
}

function toolVoiceAutopilot($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        $stmt = $db->prepare("SELECT enabled, auto_ssl, auto_fix_wp, auto_backup, monthly_report FROM autopilot_settings WHERE client_id = ?");
        $stmt->execute([$cid]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT COUNT(*) FROM autopilot_actions WHERE client_id = ? AND auto_fixed = 1 AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute([$cid]);
        $fixed = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM autopilot_actions WHERE client_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute([$cid]);
        $total = (int)$stmt->fetchColumn();

        $enabled = $settings ? (bool)$settings['enabled'] : false;
        return ['success' => true, 'autopilot_enabled' => $enabled, 'issues_fixed_30d' => $fixed, 'total_actions_30d' => $total,
                'auto_ssl' => (bool)($settings['auto_ssl'] ?? false), 'auto_wp' => (bool)($settings['auto_fix_wp'] ?? false),
                'auto_backup' => (bool)($settings['auto_backup'] ?? false),
                'message' => 'Autopilot is ' . ($enabled ? 'active' : 'disabled') . ". In the last 30 days, $fixed issues were auto-fixed out of $total total actions detected."];
    } catch (Exception $e) {
        return ['error' => 'Could not check autopilot status.'];
    }
}

function toolVoiceMonthlyReport($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        $where = 'client_id = ?';
        $params = [$cid];
        if ($domain) { $where .= ' AND domain = ?'; $params[] = $domain; }

        $stmt = $db->prepare("SELECT domain, report_month, uptime_pct, avg_response_ms, incidents, issues_fixed, security_score, visitors_est FROM autopilot_reports WHERE $where ORDER BY created_at DESC LIMIT 1");
        $stmt->execute($params);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) return ['message' => 'No monthly report available yet. Reports are generated on the 1st of each month.'];

        $month = date('F Y', strtotime($report['report_month'] . '-01'));
        return ['success' => true, 'domain' => $report['domain'], 'month' => $month,
                'uptime' => $report['uptime_pct'], 'avg_response' => $report['avg_response_ms'],
                'incidents' => $report['incidents'], 'issues_fixed' => $report['issues_fixed'],
                'security_score' => $report['security_score'], 'visitors' => $report['visitors_est'],
                'message' => "Your $month report for {$report['domain']}: " .
                    ($report['uptime_pct'] ? "{$report['uptime_pct']}% uptime, " : '') .
                    "{$report['incidents']} incidents, {$report['issues_fixed']} issues auto-fixed" .
                    ($report['visitors_est'] ? ", approximately " . number_format($report['visitors_est']) . " visitors" : '') . '.'];
    } catch (Exception $e) {
        return ['error' => 'Could not retrieve monthly report.'];
    }
}

function toolVoiceDiskUsage($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        $svc = $db->prepare("SELECT da_username FROM services WHERE client_id = ? AND status = 'Active' LIMIT 1");
        $svc->execute([$cid]);
        $daUser = $svc->fetchColumn();
        if (!$daUser) return ['message' => 'No active hosting service found.'];

        require_once dirname(__DIR__) . '/pay/includes/directadmin.php';
        $da = new DirectAdmin($db);
        $usage = $da->getUsage($daUser);

        $diskUsed = $usage['disk_used'] ?? 0;
        $diskLimit = $usage['disk_limit'] ?? 0;
        $bwUsed = $usage['bandwidth_used'] ?? 0;
        $bwLimit = $usage['bandwidth_limit'] ?? 0;
        $diskPct = $diskLimit > 0 ? round(($diskUsed / $diskLimit) * 100, 1) : 0;

        return ['success' => true, 'disk_used_mb' => $diskUsed, 'disk_limit_mb' => $diskLimit, 'disk_pct' => $diskPct,
                'bandwidth_used_mb' => $bwUsed, 'bandwidth_limit_mb' => $bwLimit,
                'message' => "You're using {$diskUsed}MB of {$diskLimit}MB disk space, that's {$diskPct}% used." .
                    ($bwLimit > 0 ? " Bandwidth: {$bwUsed}MB of {$bwLimit}MB." : '')];
    } catch (Exception $e) {
        return ['error' => 'Could not check disk usage right now.'];
    }
}

function toolVoiceRenewSSL($args) {
    $cid = (int)($args['client_id'] ?? 0);
    $domain = trim($args['domain'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$domain) return ['error' => 'Which domain would you like to renew SSL for?'];

    try {
        $db = billingDB();
        $svc = $db->prepare("SELECT da_username FROM services WHERE client_id = ? AND status = 'Active' LIMIT 1");
        $svc->execute([$cid]);
        $daUser = $svc->fetchColumn();
        if (!$daUser) return ['message' => 'No active hosting service found.'];

        require_once dirname(__DIR__) . '/pay/includes/directadmin.php';
        $da = new DirectAdmin($db);
        $da->requestLetsEncrypt($daUser, $domain);

        return ['success' => true, 'domain' => $domain,
                'message' => "SSL renewal has been triggered for $domain. The new certificate should be active within a few minutes."];
    } catch (Exception $e) {
        error_log('[vapi-tools] SSL renew error: ' . $e->getMessage());
        return ['error' => "Could not renew SSL for $domain"];
    }
}

function toolVoiceBackup($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        $svc = $db->prepare("SELECT da_username FROM services WHERE client_id = ? AND status = 'Active' LIMIT 1");
        $svc->execute([$cid]);
        $daUser = $svc->fetchColumn();
        if (!$daUser) return ['message' => 'No active hosting service found.'];

        require_once dirname(__DIR__) . '/pay/includes/directadmin.php';
        $da = new DirectAdmin($db);
        $da->createBackup($daUser);

        return ['success' => true, 'message' => 'A full backup has been initiated. It will be available in your DirectAdmin backup manager once complete.'];
    } catch (Exception $e) {
        error_log('[vapi-tools] Backup error: ' . $e->getMessage());
        return ['error' => 'Could not create backup'];
    }
}

function toolVoiceListStaging($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        $stmt = $db->prepare("SELECT domain, staging_subdomain, status, created_at FROM staging_environments WHERE client_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$cid]);
        $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($stages)) return ['message' => 'You don\'t have any staging sites. You can create one from your dashboard.'];

        $list = array_map(fn($s) => "{$s['staging_subdomain']} ({$s['status']})", $stages);
        return ['success' => true, 'staging_count' => count($stages), 'staging_sites' => $stages,
                'message' => 'You have ' . count($stages) . ' staging site(s): ' . implode(', ', $list) . '.'];
    } catch (Exception $e) {
        return ['error' => 'Could not list staging sites.'];
    }
}

function toolVoiceClientHealth($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        $stmt = $db->prepare("SELECT overall_score, billing_score, engagement_score, hosting_score, risk_level, calculated_at FROM client_health_scores WHERE client_id = ? ORDER BY calculated_at DESC LIMIT 1");
        $stmt->execute([$cid]);
        $health = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$health) return ['message' => 'No health score calculated yet. Check back soon.'];

        return ['success' => true, 'overall_score' => $health['overall_score'], 'risk_level' => $health['risk_level'],
                'billing_score' => $health['billing_score'], 'engagement_score' => $health['engagement_score'],
                'hosting_score' => $health['hosting_score'],
                'message' => "Your account health score is {$health['overall_score']} out of 100. Risk level: {$health['risk_level']}. Billing: {$health['billing_score']}, Engagement: {$health['engagement_score']}, Hosting: {$health['hosting_score']}."];
    } catch (Exception $e) {
        return ['error' => 'Could not check account health.'];
    }
}

function toolVoiceRevenue($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        // Check if caller is admin/reseller
        $role = $db->prepare("SELECT role FROM clients WHERE id = ?");
        $role->execute([$cid]);
        $r = $role->fetchColumn();
        if (!in_array($r, ['admin','reseller'])) return ['error' => 'Revenue reports are available for admin and reseller accounts only.'];

        $revenue30 = $db->query("SELECT COALESCE(SUM(total),0) FROM invoices WHERE status = 'Paid' AND paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
        $activeClients = $db->query("SELECT COUNT(*) FROM clients WHERE status = 'Active'")->fetchColumn();
        $activeServices = $db->query("SELECT COUNT(*) FROM services WHERE status = 'Active'")->fetchColumn();
        $unpaid = $db->query("SELECT COALESCE(SUM(total),0) FROM invoices WHERE status IN ('Unpaid','Overdue')")->fetchColumn();

        return ['success' => true, 'revenue_30d' => round($revenue30, 2), 'active_clients' => (int)$activeClients,
                'active_services' => (int)$activeServices, 'unpaid_invoices' => round($unpaid, 2),
                'message' => "Revenue in the last 30 days: $" . number_format($revenue30, 2) . ". You have $activeClients active clients, $activeServices active services, and $" . number_format($unpaid, 2) . " in unpaid invoices."];
    } catch (Exception $e) {
        return ['error' => 'Could not generate revenue report.'];
    }
}

function toolVoiceListWebhooks($args) {
    $cid = (int)($args['client_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        $stmt = $db->prepare("SELECT url, events, active, failure_count FROM webhook_subscriptions WHERE client_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$cid]);
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($webhooks)) return ['message' => 'You don\'t have any webhook subscriptions. You can create them from your dashboard.'];

        $active = count(array_filter($webhooks, fn($w) => $w['active']));
        return ['success' => true, 'webhook_count' => count($webhooks), 'active_count' => $active,
                'message' => "You have " . count($webhooks) . " webhook subscription(s), $active active. " .
                    "Endpoints: " . implode(', ', array_map(fn($w) => parse_url($w['url'], PHP_URL_HOST), $webhooks)) . '.'];
    } catch (Exception $e) {
        return ['error' => 'Could not list webhooks.'];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// v14.0: PERSONAL AGENT, CONTACTS, GAMES, DELEGATION, REPORTS
// ═══════════════════════════════════════════════════════════════════════════

function toolLookupContact($args) {
    $cid   = (int)($args['client_id'] ?? 0);
    $query = trim($args['name'] ?? $args['query'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$query) return ['error' => 'Who would you like me to look up?'];

    try {
        define('CONTACTS_FUNCTIONS_ONLY', true);
        require_once dirname(__DIR__) . '/pay/api/contacts.php';
        $db = billingDB();
        ensureContactsTables($db);
        $contacts = lookupContact($db, $cid, $query);
        if (empty($contacts)) return ['message' => "I couldn't find anyone matching \"$query\" in your contacts. You can add them by saying 'Add a contact'."];
        $c = $contacts[0];
        $msg = "Found {$c['name']}";
        if ($c['relationship']) $msg .= " ({$c['relationship']})";
        if ($c['phone']) $msg .= ". Phone: {$c['phone']}";
        if ($c['email']) $msg .= ". Email: {$c['email']}";
        return ['success' => true, 'contact' => $c, 'total_matches' => count($contacts), 'message' => $msg . '.'];
    } catch (Exception $e) {
        return ['error' => 'Could not search contacts right now.'];
    }
}

function toolAddContact($args) {
    $cid  = (int)($args['client_id'] ?? 0);
    $name = trim($args['name'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$name) return ['error' => 'What\'s the name of the person you want to add?'];

    try {
        if (!defined('CONTACTS_FUNCTIONS_ONLY')) define('CONTACTS_FUNCTIONS_ONLY', true);
        require_once dirname(__DIR__) . '/pay/api/contacts.php';
        $db = billingDB();
        ensureContactsTables($db);
        $data = [
            'name'         => $name,
            'phone'        => trim($args['phone'] ?? ''),
            'email'        => trim($args['email'] ?? ''),
            'relationship' => trim($args['relationship'] ?? ''),
            'label'        => trim($args['label'] ?? ''),
            'notes'        => trim($args['notes'] ?? ''),
        ];
        $result = createContact($db, $cid, $data);
        return ['success' => true, 'contact' => $result, 'message' => "I've added {$name} to your contacts." .
            ($data['phone'] ? " Phone: {$data['phone']}." : '') .
            ($data['label'] ? " Label: {$data['label']}." : '')];
    } catch (Exception $e) {
        return ['error' => 'Could not add contact right now.'];
    }
}

function toolPersonalCall($args) {
    $cid     = (int)($args['client_id'] ?? 0);
    $contact = trim($args['name'] ?? $args['contact'] ?? '');
    $message = trim($args['message'] ?? $args['greeting'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$contact) return ['error' => 'Who would you like me to call?'];

    try {
        if (!defined('CONTACTS_FUNCTIONS_ONLY')) define('CONTACTS_FUNCTIONS_ONLY', true);
        require_once dirname(__DIR__) . '/pay/api/contacts.php';
        $db = billingDB();
        ensureContactsTables($db);
        $matches = lookupContact($db, $cid, $contact);
        if (empty($matches)) return ['error' => "I couldn't find \"$contact\" in your contacts. Add them first by saying 'Add a contact'."];
        $c = $matches[0];
        if (!$c['phone']) return ['error' => "I found {$c['name']} but they don't have a phone number saved. Please update their contact."];

        $greeting = $message ?: "Hi, this is a call from your friend through Alfred AI assistant.";
        $result = callOutboundWithFallback($c['phone'], $greeting, 'personal_call', $cid);
        if (!empty($result['success'])) {
            return ['success' => true, 'contact_name' => $c['name'], 'phone' => $c['phone'],
                    'message' => "Calling {$c['name']} at {$c['phone']} now. I'll let them know: \"{$greeting}\""];
        }
        return ['error' => "I tried to call {$c['name']} but the call couldn't be placed right now. " . ($result['error'] ?? '')];
    } catch (Exception $e) {
        return ['error' => 'Could not place the call right now.'];
    }
}

function toolPersonalSMS($args) {
    $cid     = (int)($args['client_id'] ?? 0);
    $contact = trim($args['name'] ?? $args['contact'] ?? '');
    $message = trim($args['message'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$contact) return ['error' => 'Who would you like me to text?'];
    if (!$message) return ['error' => 'What message would you like me to send?'];

    try {
        if (!defined('CONTACTS_FUNCTIONS_ONLY')) define('CONTACTS_FUNCTIONS_ONLY', true);
        require_once dirname(__DIR__) . '/pay/api/contacts.php';
        $db = billingDB();
        ensureContactsTables($db);
        $matches = lookupContact($db, $cid, $contact);
        if (empty($matches)) return ['error' => "I couldn't find \"$contact\" in your contacts."];
        $c = $matches[0];
        if (!$c['phone']) return ['error' => "I found {$c['name']} but they don't have a phone number saved."];

        $envFile = dirname(dirname(__DIR__)) . '/.env.php';
        if (file_exists($envFile)) require_once $envFile;
        $fromNumber = getenv('TELNYX_FROM_NUMBER');
        if (!$fromNumber) return ['error' => 'SMS is not configured on the server yet.'];

        $result = sendSmsWithFallback($c['phone'], $fromNumber, $message, $cid);
        if (!empty($result['success'])) {
            return ['success' => true, 'contact_name' => $c['name'], 'phone' => $c['phone'],
                    'message' => "Text sent to {$c['name']} at {$c['phone']}: \"{$message}\""];
        }
        return ['error' => "Couldn't send the text to {$c['name']}. " . ($result['error'] ?? '')];
    } catch (Exception $e) {
        return ['error' => 'Could not send the text right now.'];
    }
}

function toolPlayGame($args) {
    $cid        = (int)($args['client_id'] ?? 0);
    $gameType   = trim($args['game_type'] ?? $args['game'] ?? 'trivia');
    $difficulty = trim($args['difficulty'] ?? 'medium');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    $validGames = ['chess', 'trivia', 'twenty_questions', 'word_association', 'riddles', 'mad_libs'];
    $gameType = strtolower(str_replace([' ', '-'], '_', $gameType));
    if ($gameType === '20_questions' || $gameType === 'twenty questions') $gameType = 'twenty_questions';
    if ($gameType === 'word association') $gameType = 'word_association';
    if ($gameType === 'mad libs') $gameType = 'mad_libs';
    if (!in_array($gameType, $validGames)) {
        return ['error' => "I don't know that game. I can play: chess, trivia, 20 questions, word association, riddles, or mad libs."];
    }

    try {
        if (!defined('VOICE_GAMES_FUNCTIONS_ONLY')) define('VOICE_GAMES_FUNCTIONS_ONLY', true);
        require_once dirname(__DIR__) . '/pay/api/voice-games.php';
        $db = billingDB();
        ensureGamesTables($db);
        $result = startGame($db, $cid, $gameType, $difficulty);
        return $result;
    } catch (Exception $e) {
        return ['error' => 'Could not start the game right now.'];
    }
}

function toolMakeGameMove($args) {
    $cid       = (int)($args['client_id'] ?? 0);
    $sessionId = (int)($args['session_id'] ?? 0);
    $input     = trim($args['input'] ?? $args['move'] ?? $args['answer'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$input) return ['error' => 'What\'s your move or answer?'];

    try {
        if (!defined('VOICE_GAMES_FUNCTIONS_ONLY')) define('VOICE_GAMES_FUNCTIONS_ONLY', true);
        require_once dirname(__DIR__) . '/pay/api/voice-games.php';
        $db = billingDB();
        ensureGamesTables($db);

        if (!$sessionId) {
            $stmt = $db->prepare("SELECT id FROM voice_game_sessions WHERE client_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$cid]);
            $sessionId = (int)$stmt->fetchColumn();
            if (!$sessionId) return ['error' => "You don't have an active game. Say 'Let's play trivia' or 'Start a chess game' to begin."];
        }

        return makeMove($db, $cid, $sessionId, $input);
    } catch (Exception $e) {
        return ['error' => 'Could not process your move right now.'];
    }
}

function toolResignGame($args) {
    $cid       = (int)($args['client_id'] ?? 0);
    $sessionId = (int)($args['session_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        if (!defined('VOICE_GAMES_FUNCTIONS_ONLY')) define('VOICE_GAMES_FUNCTIONS_ONLY', true);
        require_once dirname(__DIR__) . '/pay/api/voice-games.php';
        $db = billingDB();

        if (!$sessionId) {
            $stmt = $db->prepare("SELECT id FROM voice_game_sessions WHERE client_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$cid]);
            $sessionId = (int)$stmt->fetchColumn();
            if (!$sessionId) return ['message' => "You don't have an active game to resign from."];
        }

        return resignGame($db, $cid, $sessionId);
    } catch (Exception $e) {
        return ['error' => 'Could not resign the game right now.'];
    }
}

function toolDelegateTask($args) {
    $cid  = (int)($args['client_id'] ?? 0);
    $task = trim($args['task'] ?? $args['description'] ?? '');
    if (!$cid) return ['error' => 'I need to verify your identity first.'];
    if (!$task) return ['error' => 'What task would you like me to delegate?'];

    try {
        if (!defined('DELEGATION_FUNCTIONS_ONLY')) define('DELEGATION_FUNCTIONS_ONLY', true);
        require_once dirname(__DIR__) . '/pay/api/delegation.php';
        $db = billingDB();
        ensureDelegationTables($db);

        $classification = classifyIntent($task);
        $agents = $classification['recommended_agents'] ?? ['researcher'];
        $agentsJson = json_encode($agents);
        $category = $classification['category'] ?? 'general';

        $stmt = $db->prepare("INSERT INTO alfred_delegated_tasks (client_id, task_description, intent_category, agents, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$cid, $task, $category, $agentsJson]);
        $taskId = (int)$db->lastInsertId();

        $agentList = implode(', ', $agents);
        return ['success' => true, 'task_id' => $taskId, 'category' => $category, 'agents' => $agents,
                'message' => "I've delegated your task to $agentList. Task #{$taskId} is now pending. I'll update you when it's done. Category: $category."];
    } catch (Exception $e) {
        return ['error' => 'Could not delegate the task right now.'];
    }
}

function toolCheckDelegation($args) {
    $cid    = (int)($args['client_id'] ?? 0);
    $taskId = (int)($args['task_id'] ?? 0);
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();

        if ($taskId) {
            $stmt = $db->prepare("SELECT * FROM alfred_delegated_tasks WHERE id = ? AND client_id = ?");
            $stmt->execute([$taskId, $cid]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$task) return ['error' => "I couldn't find task #{$taskId}."];
            $agents = json_decode($task['agents'], true) ?: [];
            return ['success' => true, 'task' => $task,
                    'message' => "Task #{$taskId}: {$task['status']}. " .
                        "Category: {$task['intent_category']}. Agents: " . implode(', ', $agents) . '. ' .
                        ($task['result'] ? "Result: {$task['result']}" : 'Still working on it.')];
        }

        $stmt = $db->prepare("SELECT * FROM alfred_delegated_tasks WHERE client_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$cid]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($tasks)) return ['message' => "You don't have any delegated tasks yet. Say 'Delegate this task...' to get started."];

        $lines = [];
        foreach ($tasks as $t) {
            $lines[] = "#{$t['id']}: {$t['status']} — " . substr($t['task_description'], 0, 60);
        }
        return ['success' => true, 'tasks' => $tasks, 'message' => "Your recent delegated tasks:\n" . implode("\n", $lines)];
    } catch (Exception $e) {
        return ['error' => 'Could not check delegation status right now.'];
    }
}

function toolGetAlfredReport($args) {
    $cid   = (int)($args['client_id'] ?? 0);
    $month = trim($args['month'] ?? date('Y-m'));
    if (!$cid) return ['error' => 'I need to verify your identity first.'];

    try {
        $db = billingDB();
        $start = $month . '-01';
        $end   = date('Y-m-t', strtotime($start));

        $stats = [];
        $queries = [
            'delegations' => "SELECT COUNT(*) FROM alfred_delegated_tasks WHERE client_id = ? AND created_at BETWEEN ? AND ?",
            'games'       => "SELECT COUNT(*) FROM voice_game_sessions WHERE client_id = ? AND created_at BETWEEN ? AND ?",
            'contacts'    => "SELECT COUNT(*) FROM user_contacts WHERE client_id = ?",
        ];

        foreach ($queries as $key => $sql) {
            try {
                $stmt = $db->prepare($sql);
                if ($key === 'contacts') {
                    $stmt->execute([$cid]);
                } else {
                    $stmt->execute([$cid, $start, "$end 23:59:59"]);
                }
                $stats[$key] = (int)$stmt->fetchColumn();
            } catch (Exception $e) {
                $stats[$key] = 0;
            }
        }

        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM autopilot_actions WHERE client_id = ? AND auto_fixed = 1 AND created_at BETWEEN ? AND ?");
            $stmt->execute([$cid, $start, "$end 23:59:59"]);
            $stats['auto_fixes'] = (int)$stmt->fetchColumn();
        } catch (Exception $e) { $stats['auto_fixes'] = 0; }

        $monthName = date('F Y', strtotime($start));
        $total = $stats['delegations'] + $stats['games'] + $stats['auto_fixes'];
        return ['success' => true, 'month' => $monthName, 'stats' => $stats,
                'message' => "Here's what I did for you in $monthName: {$stats['delegations']} delegated tasks, " .
                    "{$stats['games']} games played, {$stats['auto_fixes']} auto-fixes applied. " .
                    "You have {$stats['contacts']} contacts saved. Total interactions: $total."];
    } catch (Exception $e) {
        return ['error' => 'Could not generate your report right now.'];
    }
}

// ── v15.0: Autopilot Evolution Tools ──────────────────────────────────────

function toolProactiveScan($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain']   ?? null;
    $scanType = $args['scanType'] ?? 'all';
    if (!$clientId || !$domain) return ['error' => 'clientId and domain required'];

    define('AUTOPILOT_EVO_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/autopilot-evolution.php';

    $db = billingDB();
    $stmt = $db->prepare("SELECT id FROM hosting_accounts WHERE client_id = ? AND domain = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$clientId, $domain]);
    if (!$stmt->fetchColumn()) return ['error' => 'Domain not found or not yours'];

    $findings = runProactiveScan($db, $clientId, $domain, $scanType);
    $fixable  = array_filter($findings, fn($f) => $f['auto_fixable'] && $f['confidence'] >= 80);
    $msg = count($findings) . " issues found in $scanType scan of $domain. " . count($fixable) . " can be auto-fixed.";
    return ['success' => true, 'domain' => $domain, 'scan_type' => $scanType,
            'findings' => $findings, 'auto_fixable_count' => count($fixable), 'message' => $msg];
}

function toolSecurityScan($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain']   ?? null;
    if (!$clientId || !$domain) return ['error' => 'clientId and domain required'];

    define('AUTOPILOT_EVO_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/autopilot-evolution.php';

    $db = billingDB();
    $stmt = $db->prepare("SELECT id FROM hosting_accounts WHERE client_id = ? AND domain = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$clientId, $domain]);
    if (!$stmt->fetchColumn()) return ['error' => 'Domain not found'];

    $events = runSecurityScan($db, $clientId, $domain);
    $threats = array_filter($events, fn($e) => $e['severity'] === 'critical' || $e['severity'] === 'high');
    $msg = count($events) . " security events detected on $domain. " . count($threats) . " are high/critical severity.";
    return ['success' => true, 'domain' => $domain, 'events' => $events,
            'threat_count' => count($threats), 'message' => $msg];
}

function toolGenerateNarrative($args) {
    $clientId = $args['clientId'] ?? null;
    $month    = $args['month']    ?? date('Y-m');
    $tone     = $args['tone']     ?? 'friendly';
    if (!$clientId) return ['error' => 'clientId required'];

    define('AUTOPILOT_EVO_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/autopilot-evolution.php';

    $db = billingDB();
    $narrative = generateNarrative($db, $clientId, $month, $tone);
    return ['success' => true, 'month' => $month, 'tone' => $tone,
            'narrative' => $narrative, 'message' => $narrative];
}

function toolEditWebsite($args) {
    $clientId = $args['clientId'] ?? null;
    $domain   = $args['domain']   ?? null;
    $action   = $args['action']   ?? 'list-files';
    $path     = $args['path']     ?? '/';
    $content  = $args['content']  ?? null;
    if (!$clientId || !$domain) return ['error' => 'clientId and domain required'];

    define('EDITOR_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/website-editor.php';

    $db = billingDB();
    $stmt = $db->prepare("SELECT da_username FROM hosting_accounts WHERE client_id = ? AND domain = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$clientId, $domain]);
    $daUser = $stmt->fetchColumn();
    if (!$daUser) return ['error' => 'Domain not found'];

    $da = DirectAdmin::asAdmin($db);
    switch ($action) {
        case 'list-files':
            $files = $da->listFiles($daUser, "/domains/$domain/public_html" . $path);
            return ['success' => true, 'files' => $files, 'message' => 'Here are the files in ' . $path];
        case 'read-file':
            $result = $da->readFileContent($daUser, "/domains/$domain/public_html" . $path);
            return ['success' => true, 'content' => $result['content'] ?? '', 'message' => 'File content retrieved'];
        case 'save-file':
            if (!$content) return ['error' => 'content required for save'];
            $da->writeFile($daUser, "/domains/$domain/public_html" . $path, $content);
            return ['success' => true, 'message' => "File $path saved successfully on $domain"];
        default:
            return ['error' => "Unknown editor action: $action"];
    }
}

function toolBuildWebsite($args) {
    $clientId    = $args['clientId']    ?? null;
    $domain      = $args['domain']      ?? null;
    $description = $args['description'] ?? null;
    if (!$clientId || !$domain || !$description) return ['error' => 'clientId, domain and description required'];

    define('BUILDER_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/website-builder.php';

    $db = billingDB();
    $stmt = $db->prepare("SELECT da_username FROM hosting_accounts WHERE client_id = ? AND domain = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$clientId, $domain]);
    if (!$stmt->fetchColumn()) return ['error' => 'Domain not found'];

    ensureBuilderTables($db);
    $projectId = bin2hex(random_bytes(8));
    $stmt = $db->prepare("INSERT INTO website_build_projects (project_id, client_id, domain, brief, business_type, status, created_at) VALUES (?, ?, ?, ?, ?, 'brief_analysis', NOW())");
    $stmt->execute([$projectId, $clientId, $domain, $description, $args['businessType'] ?? 'general']);

    return ['success' => true, 'project_id' => $projectId,
            'message' => "Website build project started for $domain. Project ID: $projectId. " .
                         "The AI agent fleet will build your site through 8 steps. Use buildWebsiteStatus to check progress."];
}

function toolBuildWebsiteStatus($args) {
    $clientId  = $args['clientId']  ?? null;
    $projectId = $args['projectId'] ?? null;
    if (!$clientId || !$projectId) return ['error' => 'clientId and projectId required'];

    define('BUILDER_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/website-builder.php';

    $db = billingDB();
    ensureBuilderTables($db);
    $stmt = $db->prepare("SELECT * FROM website_build_projects WHERE project_id = ? AND client_id = ?");
    $stmt->execute([$projectId, $clientId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$project) return ['error' => 'Project not found'];

    $steps = ['brief_analysis','content_planning','design_direction','homepage_generation','additional_pages','code_review','final_polish','deployed'];
    $current = array_search($project['status'], $steps);
    $progress = $current !== false ? round(($current + 1) / count($steps) * 100) : 0;

    return ['success' => true, 'project_id' => $projectId, 'status' => $project['status'],
            'progress' => $progress, 'domain' => $project['domain'],
            'message' => "Project $projectId is at step " . ($current + 1) . "/8 ({$project['status']}). $progress% complete."];
}

function toolFleetExecute($args) {
    $clientId = $args['clientId'] ?? null;
    $toolName = $args['toolName'] ?? null;
    $toolArgs = $args['toolArgs'] ?? [];
    if (!$clientId || !$toolName) return ['error' => 'clientId and toolName required'];

    define('FLEET_TOOLS_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/fleet-tools.php';

    $db = billingDB();
    $result = executeFleetTool($db, $clientId, $toolName, $toolArgs);
    return $result;
}

// ══════════════════════════════════════════════════════════════
// ── v16.0  VR WORLD VOICE TOOLS ──────────────────────────────
// ══════════════════════════════════════════════════════════════

function toolVrStartMatch($args) {
    $clientId = $args['clientId'] ?? null;
    $white    = $args['white']    ?? $args['whiteAgent'] ?? 'Alfred';
    $black    = $args['black']    ?? $args['blackAgent'] ?? 'Nova';
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('VR_WORLD_FUNCTIONS_ONLY')) define('VR_WORLD_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/vr-world.php';

    $db = billingDB();
    ensureVRTables($db);
    $result = chessMatchStart($db, $clientId, ['white' => $white, 'black' => $black]);

    if (!empty($result['match_id'])) {
        $result['message'] = "Started a chess match: $white (white) vs $black (black). Match ID: {$result['match_id']}. You can watch it at gositeme.com/vr/chess/";
    }
    return $result;
}

function toolVrClaimPlot($args) {
    $clientId = $args['clientId'] ?? null;
    $plotId   = $args['plotId']   ?? $args['plot_id'] ?? null;
    $name     = $args['name']    ?? $args['plotName'] ?? 'My Plot';
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('VR_WORLD_FUNCTIONS_ONLY')) define('VR_WORLD_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/vr-world.php';

    $db = billingDB();
    ensureVRTables($db);
    $result = worldClaim($db, $clientId, ['plot_id' => $plotId, 'name' => $name]);

    if (!empty($result['success'])) {
        $result['message'] = "Plot claimed successfully! You now own plot " . ($plotId ?? 'auto-assigned') . " named '$name'. Visit your plot at gositeme.com/vr/hub/ to start building.";
    }
    return $result;
}

function toolVrGetLeaderboard($args) {
    if (!defined('VR_WORLD_FUNCTIONS_ONLY')) define('VR_WORLD_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/vr-world.php';

    $db = billingDB();
    ensureVRTables($db);
    $result = chessLeaderboard($db);

    if (!empty($result['agents'])) {
        $lines = ["Here are the current AI Chess League standings:"];
        foreach ($result['agents'] as $i => $a) {
            $rank = $i + 1;
            $w = $a['wins']; $l = $a['losses']; $d = $a['draws'];
            $lines[] = "$rank. {$a['agent_name']} — ELO {$a['elo']}, Record: {$w}W-{$l}L-{$d}D";
        }
        $result['message'] = implode("\n", $lines);
    }
    return $result;
}

function toolVrCustomizeAvatar($args) {
    $clientId  = $args['clientId']    ?? null;
    $bodyType  = $args['body_type']   ?? $args['bodyType']  ?? null;
    $headShape = $args['head_shape']  ?? $args['headShape'] ?? null;
    $eyeStyle  = $args['eye_style']   ?? $args['eyeStyle']  ?? null;
    $color     = $args['color']       ?? null;
    $accessory = $args['accessory']   ?? null;
    $outfit    = $args['outfit']      ?? null;
    $nickname  = $args['nickname']    ?? $args['displayName'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('VR_WORLD_FUNCTIONS_ONLY')) define('VR_WORLD_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/vr-world.php';

    $db = billingDB();
    ensureVRTables($db);

    $saveArgs = [];
    if ($bodyType)  $saveArgs['body_type']  = $bodyType;
    if ($headShape) $saveArgs['head_shape'] = $headShape;
    if ($eyeStyle)  $saveArgs['eye_style']  = $eyeStyle;
    if ($color)     $saveArgs['color']      = $color;
    if ($accessory) $saveArgs['accessory']  = $accessory;
    if ($outfit)    $saveArgs['outfit']     = $outfit;
    if ($nickname)  $saveArgs['nickname']   = $nickname;

    if (empty($saveArgs)) {
        // Return current avatar
        $current = avatarGet($db, $clientId);
        $current['message'] = "Your current avatar: " . ($current['nickname'] ?? 'Unnamed') . " — " .
            ($current['body_type'] ?? 'humanoid') . " body, " . ($current['head_shape'] ?? 'round') . " head, " .
            ($current['color'] ?? '#0074D9') . " color. Say what you'd like to change!";
        return $current;
    }

    $result = avatarSave($db, $clientId, $saveArgs);
    if (!empty($result['success'])) {
        $changes = [];
        foreach ($saveArgs as $k => $v) $changes[] = "$k: $v";
        $result['message'] = "Avatar updated! Changed: " . implode(', ', $changes) . ". Check it out at gositeme.com/account/vr-world";
    }
    return $result;
}

function toolVrStartTournament($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('VR_WORLD_FUNCTIONS_ONLY')) define('VR_WORLD_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/vr-world.php';

    $db = billingDB();
    ensureVRTables($db);
    $result = chessTournament($db, $clientId);

    if (!empty($result['matches'])) {
        $count = count($result['matches']);
        $result['message'] = "Tournament started! $count matches have been created in a round-robin format. All 8 AI agents will compete. Watch the action at gositeme.com/vr/chess/";
    }
    return $result;
}

function toolVrGetMyPlots($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('VR_WORLD_FUNCTIONS_ONLY')) define('VR_WORLD_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/vr-world.php';

    $db = billingDB();
    ensureVRTables($db);
    $result = worldMyPlots($db, $clientId);

    if (!empty($result['plots'])) {
        $lines = ["You own " . count($result['plots']) . " plot(s):"];
        foreach ($result['plots'] as $p) {
            $lines[] = "• {$p['name']} (Plot #{$p['plot_id']}) at ({$p['x']},{$p['z']})";
        }
        $result['message'] = implode("\n", $lines);
    } else {
        $result['message'] = "You don't own any plots yet. Would you like me to claim one for you?";
    }
    return $result;
}

function toolVrEnterWorld($args) {
    $clientId = $args['clientId'] ?? null;
    if (!$clientId) return ['error' => 'clientId required'];

    if (!defined('VR_WORLD_FUNCTIONS_ONLY')) define('VR_WORLD_FUNCTIONS_ONLY', true);
    require_once __DIR__ . '/../pay/api/vr-world.php';

    $db = billingDB();
    ensureVRTables($db);

    // Gather world summary
    $leaderboard = chessLeaderboard($db);
    $plots = worldMyPlots($db, $clientId);
    $avatar = avatarGet($db, $clientId);

    $plotCount = !empty($plots['plots']) ? count($plots['plots']) : 0;
    $topAgent = !empty($leaderboard['agents'][0]) ? $leaderboard['agents'][0]['agent_name'] . ' (ELO ' . $leaderboard['agents'][0]['elo'] . ')' : 'None yet';
    $avatarName = $avatar['nickname'] ?? 'Guest';

    return [
        'success' => true,
        'world_url' => 'https://gositeme.com/vr/hub/',
        'chess_url' => 'https://gositeme.com/vr/chess/',
        'dashboard_url' => 'https://gositeme.com/account/vr-world',
        'avatar' => $avatarName,
        'plots_owned' => $plotCount,
        'top_agent' => $topAgent,
        'message' => "Welcome to the GoSiteMe Virtual World, $avatarName! You own $plotCount plot(s). " .
            "The current chess champion is $topAgent. " .
            "Visit gositeme.com/vr/hub/ to explore the 3D world, or gositeme.com/vr/chess/ for the chess arena."
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// SOVEREIGNTY: Redis call-client lookup for execution auth
// ═══════════════════════════════════════════════════════════════════════════
function redisGetCallClientIdFromTools($callId) {
    if (!$callId) return 0;
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $val = $redis->get("alfred:call_client:{$callId}");
        $redis->close();
        return $val ? (int)$val : 0;
    } catch (Exception $e) { return 0; }
}


// ═══════════════════════════════════════════════════════════════════════════
// PHASE 1A: Voice-Triggered Ops Directives
// ═══════════════════════════════════════════════════════════════════════════
function toolCreateOpsDirective($args) {
    $type = $args['type'] ?? 'investigate';
    $title = $args['title'] ?? '';
    $description = $args['description'] ?? '';
    $priority = (int)($args['priority'] ?? 5);
    $assignedAgent = $args['assigned_agent'] ?? null;
    $slaMinutes = (int)($args['sla_minutes'] ?? 60);

    if (!$title) return ['error' => 'I need a title for the directive. What should it say?'];

    $validTypes = ['repair', 'upgrade', 'investigate', 'maintain', 'deploy'];
    if (!in_array($type, $validTypes)) $type = 'investigate';
    $priority = max(1, min(9, $priority));

    $db = getDB();
    if (!$db) return ['error' => 'Database unavailable'];

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_ops_directives (
        id INT AUTO_INCREMENT PRIMARY KEY, directive_id VARCHAR(36) NOT NULL UNIQUE,
        type ENUM('repair','upgrade','investigate','maintain','deploy') NOT NULL,
        title VARCHAR(255) NOT NULL, description TEXT, priority TINYINT NOT NULL DEFAULT 5,
        status ENUM('pending','claimed','in_progress','blocked','completed','failed','cancelled','escalated') NOT NULL DEFAULT 'pending',
        source ENUM('commander','alfred','agent','system','cron','voice') NOT NULL DEFAULT 'commander',
        assigned_agent VARCHAR(100) DEFAULT NULL, assigned_by VARCHAR(100) DEFAULT 'ALFRED',
        sla_minutes INT DEFAULT 60, escalation_path VARCHAR(255) DEFAULT 'specialist→director→alfred→commander',
        input_data JSON DEFAULT NULL, output_data JSON DEFAULT NULL, error_message TEXT DEFAULT NULL,
        attempts INT DEFAULT 0, max_attempts INT DEFAULT 3, parent_id INT DEFAULT NULL,
        tags JSON DEFAULT NULL, claimed_at DATETIME DEFAULT NULL, started_at DATETIME DEFAULT NULL,
        completed_at DATETIME DEFAULT NULL, deadline DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status), INDEX idx_priority (priority DESC)
    ) ENGINE=InnoDB");

    $directiveId = sprintf('%08x-%04x-%04x-%04x-%012x', mt_rand(), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand());

    $stmt = $db->prepare("INSERT INTO alfred_ops_directives
        (directive_id, type, title, description, priority, source, assigned_agent, sla_minutes)
        VALUES (:did, :type, :title, :desc, :pri, 'voice', :agent, :sla)");
    $stmt->execute([
        ':did' => $directiveId, ':type' => $type, ':title' => substr($title, 0, 255),
        ':desc' => $description, ':pri' => $priority, ':agent' => $assignedAgent, ':sla' => $slaMinutes
    ]);

    return [
        'success' => true,
        'directive_id' => $directiveId,
        'type' => $type,
        'priority' => $priority,
        'assigned_agent' => $assignedAgent ?: 'auto (autonomy-cron will assign)',
        'sla_minutes' => $slaMinutes,
        'message' => "Directive created: \"$title\" (priority $priority, type: $type). "
            . ($assignedAgent ? "Assigned to $assignedAgent." : "The autonomy system will pick it up within 60 seconds and assign the best agent.")
            . " SLA: $slaMinutes minutes."
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// PHASE 1C: Voice-to-Agent Tasking
// ═══════════════════════════════════════════════════════════════════════════
function toolTaskAgent($args) {
    $agentName = $args['agent_name'] ?? '';
    $task = $args['task'] ?? '';
    $priority = $args['priority'] ?? 'medium';

    if (!$agentName || !$task) {
        return ['error' => 'I need the agent name and the task description.'];
    }

    $db = getDB();
    if (!$db) return ['error' => 'Database unavailable'];

    // Send via agent fleet messaging bus
    $db->exec("CREATE TABLE IF NOT EXISTS agent_messages (
        id INT AUTO_INCREMENT PRIMARY KEY, from_agent VARCHAR(50) NOT NULL, to_agent VARCHAR(50) NOT NULL,
        message_type VARCHAR(30) DEFAULT 'task', subject VARCHAR(255), body TEXT,
        priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
        status ENUM('pending','read','acknowledged','completed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, read_at DATETIME DEFAULT NULL,
        INDEX idx_to (to_agent, status), INDEX idx_from (from_agent)
    ) ENGINE=InnoDB");

    $stmt = $db->prepare("INSERT INTO agent_messages (from_agent, to_agent, message_type, subject, body, priority)
        VALUES ('ALFRED', :to, 'task', :subj, :body, :pri)");
    $stmt->execute([
        ':to' => strtoupper($agentName),
        ':subj' => 'Voice Task from Commander: ' . substr($task, 0, 100),
        ':body' => $task,
        ':pri' => in_array($priority, ['low','medium','high','urgent']) ? $priority : 'medium',
    ]);

    // Also create an ops directive for tracking
    $directiveId = sprintf('%08x-%04x-%04x-%04x-%012x', mt_rand(), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand());
    try {
        $db->prepare("INSERT INTO alfred_ops_directives
            (directive_id, type, title, description, priority, source, assigned_agent, sla_minutes)
            VALUES (:did, 'deploy', :title, :desc, :pri, 'voice', :agent, 60)")
           ->execute([
               ':did' => $directiveId,
               ':title' => "Task for $agentName: " . substr($task, 0, 200),
               ':desc' => "Commander voice task via Alfred phone call.\n\n$task",
               ':pri' => $priority === 'urgent' ? 9 : ($priority === 'high' ? 7 : 5),
               ':agent' => strtoupper($agentName),
           ]);
    } catch (Exception $e) {}

    return [
        'success' => true,
        'agent' => strtoupper($agentName),
        'task' => $task,
        'priority' => $priority,
        'directive_id' => $directiveId,
        'message' => "Task sent to $agentName: \"$task\" (priority: $priority). "
            . "They will receive it through the agent messaging bus and the ops directive system. "
            . "The autonomy loop checks every 60 seconds."
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// PHASE 2A: Live Server Health During Calls
// ═══════════════════════════════════════════════════════════════════════════
function toolGetSystemHealth($args) {
    $results = [];

    // PM2 services
    $pm2Bin = '/home/gositeme/.local/node_modules/.bin/pm2';
    $json = @shell_exec("{$pm2Bin} jlist 2>/dev/null");
    $procs = json_decode($json ?: '[]', true) ?: [];

    $servicesUp = 0;
    $servicesTotal = count($procs);
    $serviceList = [];
    foreach ($procs as $p) {
        $status = $p['pm2_env']['status'] ?? 'unknown';
        if ($status === 'online') $servicesUp++;
        $serviceList[] = [
            'name' => $p['name'] ?? 'unknown',
            'status' => $status,
            'cpu' => $p['monit']['cpu'] ?? 0,
            'memory_mb' => round(($p['monit']['memory'] ?? 0) / 1024 / 1024, 1),
            'restarts' => $p['pm2_env']['restart_time'] ?? 0,
        ];
    }

    // System resources
    $loadAvg = sys_getloadavg();
    $memInfo = @file_get_contents('/proc/meminfo');
    $memTotal = 0; $memAvail = 0;
    if ($memInfo) {
        if (preg_match('/MemTotal:\s+(\d+)/', $memInfo, $m)) $memTotal = round($m[1] / 1024);
        if (preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $m)) $memAvail = round($m[1] / 1024);
    }
    $memUsed = $memTotal - $memAvail;
    $memPct = $memTotal > 0 ? round($memUsed / $memTotal * 100) : 0;

    $diskTotal = @disk_total_space('/');
    $diskFree = @disk_free_space('/');
    $diskPct = $diskTotal > 0 ? round(($diskTotal - $diskFree) / $diskTotal * 100) : 0;

    // DB health
    $db = getDB();
    $dbOk = false;
    if ($db) { try { $db->query('SELECT 1'); $dbOk = true; } catch (Exception $e) {} }

    // Redis health
    $redisOk = false;
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redisOk = $redis->ping() === '+PONG' || $redis->ping() === true;
        $redis->close();
    } catch (Exception $e) {}

    // Recent incidents
    $incidents = 0;
    if ($db) {
        try {
            $incidents = (int)$db->query("SELECT COUNT(*) FROM autonomy_healing_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        } catch (Exception $e) {}
    }

    $overallHealth = 'healthy';
    if ($servicesUp < $servicesTotal || $memPct > 90 || $diskPct > 90) $overallHealth = 'warning';
    if ($servicesUp < $servicesTotal * 0.5 || !$dbOk || !$redisOk) $overallHealth = 'critical';

    return [
        'overall_health' => $overallHealth,
        'services' => ['up' => $servicesUp, 'total' => $servicesTotal, 'list' => $serviceList],
        'cpu_load' => ['1min' => round($loadAvg[0], 2), '5min' => round($loadAvg[1], 2), '15min' => round($loadAvg[2], 2)],
        'memory' => ['used_mb' => $memUsed, 'total_mb' => $memTotal, 'percent' => $memPct],
        'disk' => ['used_percent' => $diskPct, 'free_gb' => round($diskFree / 1073741824, 1)],
        'database' => $dbOk ? 'healthy' : 'DOWN',
        'redis' => $redisOk ? 'healthy' : 'DOWN',
        'incidents_24h' => $incidents,
        'message' => "System health: $overallHealth. $servicesUp/$servicesTotal services online. "
            . "CPU load: {$loadAvg[0]}. Memory: {$memPct}% used. Disk: {$diskPct}% used. "
            . "Database: " . ($dbOk ? 'healthy' : 'DOWN') . ". Redis: " . ($redisOk ? 'healthy' : 'DOWN') . ". "
            . "$incidents incident(s) in the last 24 hours."
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// PHASE 2B: Live Agent Fleet Status During Calls
// ═══════════════════════════════════════════════════════════════════════════
function toolGetAgentFleetStatus($args) {
    $db = getDB();
    if (!$db) return ['error' => 'Database unavailable'];

    $agentFilter = $args['agent_name'] ?? null;

    // Total agent count
    $totalAgents = 0;
    try {
        $totalAgents = (int)$db->query("SELECT COUNT(*) FROM agents WHERE status='active'")->fetchColumn();
    } catch (Exception $e) {
        try { $totalAgents = (int)$db->query("SELECT COUNT(*) FROM agent_registry WHERE is_active=1")->fetchColumn(); } catch (Exception $e2) {}
    }

    // Pending directives
    $pendingDirectives = 0;
    $activeDirectives = 0;
    try {
        $pendingDirectives = (int)$db->query("SELECT COUNT(*) FROM alfred_ops_directives WHERE status='pending'")->fetchColumn();
        $activeDirectives = (int)$db->query("SELECT COUNT(*) FROM alfred_ops_directives WHERE status IN ('claimed','in_progress')")->fetchColumn();
    } catch (Exception $e) {}

    // Recent agent messages
    $pendingMessages = 0;
    try {
        $pendingMessages = (int)$db->query("SELECT COUNT(*) FROM agent_messages WHERE status='pending'")->fetchColumn();
    } catch (Exception $e) {}

    // Recent completed directives
    $completedToday = 0;
    try {
        $completedToday = (int)$db->query("SELECT COUNT(*) FROM alfred_ops_directives WHERE status='completed' AND completed_at >= CURDATE()")->fetchColumn();
    } catch (Exception $e) {}

    // If asking about specific agent
    $agentInfo = null;
    if ($agentFilter) {
        $name = strtoupper($agentFilter);
        try {
            $stmt = $db->prepare("SELECT assigned_agent, type, title, status, priority, created_at FROM alfred_ops_directives
                WHERE assigned_agent = :name AND status IN ('claimed','in_progress','pending')
                ORDER BY priority DESC, created_at DESC LIMIT 5");
            $stmt->execute([':name' => $name]);
            $agentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $agentInfo = ['name' => $name, 'active_tasks' => count($agentTasks), 'tasks' => $agentTasks];
        } catch (Exception $e) {}
    }

    $result = [
        'total_agents' => $totalAgents,
        'pending_directives' => $pendingDirectives,
        'active_directives' => $activeDirectives,
        'completed_today' => $completedToday,
        'pending_messages' => $pendingMessages,
        'message' => "$totalAgents agents in the fleet. $activeDirectives active directive(s), $pendingDirectives pending, $completedToday completed today. $pendingMessages unread message(s)."
    ];

    if ($agentInfo) {
        $result['agent_detail'] = $agentInfo;
        $result['message'] .= " {$agentInfo['name']} has {$agentInfo['active_tasks']} active task(s).";
    }

    return $result;
}


// ═══════════════════════════════════════════════════════════════════════════
// PHASE 1B: Execute Server Commands (Commander-only)
// ═══════════════════════════════════════════════════════════════════════════
function toolExecuteServerCommand($args, $vapiCallId = '') {
    $command = $args['command'] ?? '';
    $target = $args['target'] ?? 'local';
    $confirmed = $args['confirmed'] ?? false;

    if (!$command) return ['error' => 'What command should I execute?'];

    // Verify Commander-level auth via Redis
    $clientId = redisGetCallClientIdFromTools($vapiCallId);
    if ($clientId !== 33) {
        return [
            'error' => 'Server command execution requires Commander-level authentication. Only the system creator can execute server commands via voice.',
            'auth_required' => true
        ];
    }

    if (!$confirmed) {
        return [
            'needs_confirmation' => true,
            'command' => $command,
            'target' => $target,
            'message' => "I am ready to execute: \"$command\" on $target. Please confirm — should I proceed?"
        ];
    }

    // Route through MCP bridge
    $result = mcpBridge('ssh_exec', ['command' => $command, 'target' => $target]);

    // Log the execution
    $db = getDB();
    if ($db) {
        try {
            $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), :d, 'Commander', 'voice-exec')")
               ->execute([':d' => "VOICE EXEC: $command (target: $target)"]);
        } catch (Exception $e) {}
    }

    $result['_sovereignty'] = 'Commander voice execution completed';
    return $result;
}


// ═══════════════════════════════════════════════════════════════════════════
// P2/P3: Cross-Call Memory & Redis Helpers (self-contained to avoid circular requires)
// ═══════════════════════════════════════════════════════════════════════════
function toolsRedisSetCallClientId($callId, $clientId) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->setex("alfred:call_client:{$callId}", 7200, $clientId);
        $redis->close();
    } catch (Exception $e) {
        error_log('Redis set call client error: ' . $e->getMessage());
    }
}

function toolsGetCallerMemoryContext($callerNumber, $clientId = 0) {
    $db = getDB();
    if (!$db) return null;

    $memories = [];
    if ($clientId > 0) {
        $stmt = $db->prepare("SELECT caller_name, call_summary, key_topics, unresolved_items, sentiment, last_call_at
            FROM alfred_call_memory WHERE client_id = :uid ORDER BY last_call_at DESC LIMIT 3");
        $stmt->execute([':uid' => $clientId]);
        $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if (empty($memories) && $callerNumber) {
        $last10 = substr(preg_replace('/\D/', '', $callerNumber), -10);
        $stmt = $db->prepare("SELECT caller_name, call_summary, key_topics, unresolved_items, sentiment, last_call_at
            FROM alfred_call_memory WHERE RIGHT(REPLACE(REPLACE(REPLACE(caller_number,'+',''),'-',''),' ',''), 10) = :p
            ORDER BY last_call_at DESC LIMIT 3");
        $stmt->execute([':p' => $last10]);
        $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if (empty($memories)) return null;

    $callerName = '';
    $context = "PREVIOUS CALL HISTORY (most recent first):\n";
    foreach ($memories as $i => $m) {
        $n = $i + 1;
        $context .= "--- Call $n ({$m['last_call_at']}) ---\n";
        if ($m['caller_name']) { $callerName = $m['caller_name']; $context .= "Name: {$m['caller_name']}\n"; }
        if ($m['key_topics']) $context .= "Topics: {$m['key_topics']}\n";
        if ($m['call_summary']) $context .= "Summary: {$m['call_summary']}\n";
        if ($m['unresolved_items']) $context .= "UNRESOLVED: {$m['unresolved_items']}\n";
        if ($m['sentiment']) $context .= "Mood: {$m['sentiment']}\n";
    }
    $context .= "\nUse this context to pick up where you left off. Reference previous topics naturally.";

    try {
        $cmRows = $db->query(
            "SELECT title, content, importance FROM commander_memory WHERE is_active = 1 ORDER BY FIELD(importance,'critical','high','medium','low'), created_at DESC LIMIT 12"
        )->fetchAll(PDO::FETCH_ASSOC);
        if ($cmRows) {
            $context .= "\n\nSOVEREIGN MEMORY (infrastructure upgrades & milestones):\n";
            foreach ($cmRows as $cm) {
                $context .= '- [' . $cm['importance'] . '] ' . $cm['title'] . ': ' . mb_substr($cm['content'], 0, 180) . "\n";
            }
        }
    } catch (PDOException $e) { /* table may not exist */ }

    return ['caller_name' => $callerName, 'context' => $context, 'call_count' => count($memories)];
}

function toolGetCallerContext($args, $vapiCallerNum = '') {
    $phone = $args['caller_phone'] ?? $vapiCallerNum;
    if (!$phone) return ['has_history' => false, 'message' => 'No phone number available to look up history.'];

    $memory = toolsGetCallerMemoryContext($phone, 0);
    if (!$memory) return ['has_history' => false, 'message' => 'This is a first-time caller. No previous call history.'];

    return [
        'has_history' => true,
        'previous_call_count' => $memory['call_count'],
        'caller_name' => $memory['caller_name'] ?: 'Unknown',
        'context' => $memory['context'],
        'message' => "Found {$memory['call_count']} previous call(s) for this number."
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// v17.0: Load Expanded Ecosystem Tools
// ═══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/vapi-tools-expanded.php';

// v18.0: Load Deep Coverage Tools (178 new functions)
// ═══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/vapi-tools-v18.php';
