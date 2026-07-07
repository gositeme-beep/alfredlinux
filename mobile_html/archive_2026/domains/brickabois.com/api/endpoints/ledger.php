<?php
/**
 * The Ledger API - Governance & Transparency
 */

$db = getDBConnection();

switch ($request_method) {
    case 'GET':
        if ($action === 'proposals') {
            // Get proposals
            $village_id = $_GET['village_id'] ?? null;
            $status = $_GET['status'] ?? null;
            
            $sql = "SELECT p.*, u.username, u.display_name, v.name as village_name
                    FROM proposals p
                    JOIN users u ON p.user_id = u.id
                    LEFT JOIN villages v ON p.village_id = v.id
                    WHERE 1=1";
            
            $params = [];
            if ($village_id) {
                $sql .= " AND p.village_id = ?";
                $params[] = $village_id;
            }
            
            if ($status) {
                $sql .= " AND p.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY p.created_at DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $proposals = $stmt->fetchAll();
            
            // Get vote counts
            foreach ($proposals as &$proposal) {
                $voteStmt = $db->prepare("
                    SELECT 
                        SUM(CASE WHEN vote = 'yes' THEN weight ELSE 0 END) as yes_votes,
                        SUM(CASE WHEN vote = 'no' THEN weight ELSE 0 END) as no_votes,
                        SUM(CASE WHEN vote = 'abstain' THEN weight ELSE 0 END) as abstain_votes,
                        COUNT(*) as total_votes
                    FROM votes 
                    WHERE proposal_id = ?
                ");
                $voteStmt->execute([$proposal['id']]);
                $votes = $voteStmt->fetch();
                $proposal['votes'] = $votes;
            }
            
            jsonResponse(['proposals' => $proposals, 'count' => count($proposals)]);
        }
        
        if ($action === 'treasury') {
            // Get treasury transactions
            $village_id = $_GET['village_id'] ?? null;
            $limit = min((int)($_GET['limit'] ?? 50), 200);
            
            $sql = "SELECT t.*, u.username, u.display_name, v.name as village_name
                    FROM treasury_transactions t
                    JOIN users u ON t.created_by = u.id
                    LEFT JOIN villages v ON t.village_id = v.id
                    WHERE 1=1";
            
            $params = [];
            if ($village_id) {
                $sql .= " AND t.village_id = ?";
                $params[] = $village_id;
            }
            
            $sql .= " ORDER BY t.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $transactions = $stmt->fetchAll();
            
            // Calculate balance
            $balanceStmt = $db->prepare("
                SELECT 
                    SUM(CASE WHEN transaction_type IN ('deposit', 'income') THEN amount ELSE 0 END) -
                    SUM(CASE WHEN transaction_type IN ('withdrawal', 'expense') THEN amount ELSE 0 END) as balance
                FROM treasury_transactions
                WHERE village_id = ? OR (? IS NULL AND village_id IS NULL)
            ");
            $balanceStmt->execute([$village_id, $village_id]);
            $balance = $balanceStmt->fetch()['balance'] ?? 0;
            
            jsonResponse([
                'transactions' => $transactions,
                'balance' => $balance,
                'count' => count($transactions)
            ]);
        }
        
        errorResponse('Invalid action', 400);
        break;
        
    default:
        errorResponse('Method not allowed', 405);
}

