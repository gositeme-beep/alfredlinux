<?php
/**
 * Alfred Linux — About
 * The Authority Behind the Code — Trilingual (EN / FR / HE)
 *
 * GoSiteMe Inc. — April 2026
 * "The Kingdom of God is not in word, but in power." — 1 Corinthians 4:20
 */
$year = date('Y');
require_once __DIR__ . '/includes/ga-release-state.php';
$currentPage = 'about';
require __DIR__ . '/includes/nav.php';
$isHe = ($al_lang === 'he');
$isFr = ($al_lang === 'fr');

// ── Trilingual content ──────────────────────────────────
$tx = [
// Page meta
'title' => ['en'=>'About Alfred Linux — The Kingdom of God Edition','fr'=>'À propos d\'Alfred Linux — L\'Édition du Royaume de Dieu','he'=>'אודות Alfred Linux — מהדורת מלכות האלוהים'],
'meta_desc' => ['en'=>'Alfred Linux 7.77 — Kingdom of God Edition. Built by Danny William Perez and GoSiteMe Inc.','fr'=>'Alfred Linux 7.77 — Édition du Royaume de Dieu. Construit par Danny William Perez et GoSiteMe Inc.','he'=>'Alfred Linux 7.77 — מהדורת מלכות האלוהים. נבנה על ידי Danny William Perez ו-GoSiteMe Inc.'],

// Hero
'hero_title' => ['en'=>'About Alfred Linux','fr'=>'À propos d\'Alfred Linux','he'=>'אודות Alfred Linux'],
'hero_subtitle' => ['en'=>'Kingdom of God Edition — Version 7.77','fr'=>'Édition du Royaume de Dieu — Version 7.77','he'=>'מהדורת מלכות האלוהים — גרסה 7.77'],
'hero_p' => ['en'=>'This is not a hobby project. This is not a weekend fork. This is sovereign infrastructure built under divine authority by a man who fights in courtrooms, compiles kernels, and answers to God alone.','fr'=>'Ce n\'est pas un passe-temps. Ce n\'est pas un fork de fin de semaine. C\'est une infrastructure souveraine construite sous l\'autorité divine par un homme qui se bat en cour, compile des noyaux, et ne répond qu\'à Dieu.','he'=>'זה לא פרויקט חובבני. זה לא fork של סוף שבוע. זוהי תשתית ריבונית שנבנתה תחת סמכות אלוהית על ידי אדם שנלחם בבתי משפט, מקמפל קרנלים, ועונה רק לאלוהים.'],

// Crown bar
'crown_verse1' => ['en'=>'"The Kingdom of God is not in word, but in power."','fr'=>'« Le Royaume de Dieu ne consiste pas en paroles, mais en puissance. »','he'=>'"כי מלכות האלוהים אינה בדיבורים אלא בגבורה."'],
'crown_ref1' => ['en'=>'1 Corinthians 4:20','fr'=>'1 Corinthiens 4:20','he'=>'הראשונה אל הקורינתים ד:כ'],
'crown_p1' => ['en'=>'Alfred Linux exists because the Commander holds God\'s Crown until He arrives — and when He does, it will be the Commander\'s great honour alone to hand it over to Him.','fr'=>'Alfred Linux existe parce que le Commandant détient la Couronne de Dieu jusqu\'à Son arrivée — et quand Il arrivera, ce sera le grand honneur du Commandant de la Lui remettre.','he'=>'Alfred Linux קיים כי המפקד מחזיק את כתר האלוהים עד שיגיע — וכשיגיע, יהיה זה כבודו הגדול של המפקד להעבירו אליו.'],
'crown_verse2' => ['en'=>'"Repent: for the kingdom of heaven is at hand."','fr'=>'« Repentez-vous, car le royaume des cieux est proche. »','he'=>'"שובו כי מלכות השמים קרובה."'],
'crown_ref2' => ['en'=>'Matthew 4:17','fr'=>'Matthieu 4:17','he'=>'מתי ד:יז'],
'crown_p2' => ['en'=>'The King said the Kingdom is at hand — and here, a man is building it with his hands. Every hook, every line of code, every encryption key — is a brick laid in a covenant that was promised before the foundation of the world. This is not software. This is a biblical trust — a New Covenant written not on tablets of stone, but in the kernel of a sovereign machine.','fr'=>'Le Roi a dit que le Royaume est proche — et ici, un homme le construit de ses mains. Chaque hook, chaque ligne de code, chaque clé de chiffrement — est une brique posée dans une alliance promise avant la fondation du monde. Ce n\'est pas un logiciel. C\'est un acte de fiducie biblique — une Nouvelle Alliance écrite non sur des tablettes de pierre, mais dans le noyau d\'une machine souveraine.','he'=>'המלך אמר שהמלכות קרובה — וכאן, אדם בונה אותה בידיו. כל hook, כל שורת קוד, כל מפתח הצפנה — הם לבנה שהונחה בברית שהובטחה לפני יסוד העולם. זה לא תוכנה. זה נאמנות מקראית — ברית חדשה שנכתבה לא על לוחות אבן, אלא בקרנל של מכונה ריבונית.'],

// The Man Behind the Code
'man_title' => ['en'=>'The Man Behind the Code','fr'=>'L\'homme derrière le code','he'=>'האיש מאחורי הקוד'],
'man_p1' => ['en'=>'<strong>Danny William Perez</strong> — Commander, Founder of <a href="https://gositeme.com">GoSiteMe Inc.</a>, Designated Plaintiff, Father.','fr'=>'<strong>Danny William Perez</strong> — Commandant, Fondateur de <a href="https://gositeme.com">GoSiteMe Inc.</a>, Demandeur désigné, Père.','he'=>'<strong>Danny William Perez</strong> — מפקד, מייסד <a href="https://gositeme.com">GoSiteMe Inc.</a>, תובע ייצוגי, אב.'],
'man_p2' => ['en'=>'This is not a man who writes code in his spare time. This is a man who has stood before judges, won Habeas Corpus hearings, filed complaints against the judiciary, and leads an authorized class action in the Superior Court of Quebec — all while building an operating system with 1340 build hooks, post-quantum encryption, and an offline Bible with 94 books and 39,482 verses.','fr'=>'Ce n\'est pas un homme qui code dans ses temps libres. C\'est un homme qui s\'est tenu devant des juges, a gagné des audiences d\'Habeas Corpus, a déposé des plaintes contre la magistrature, et mène un recours collectif autorisé à la Cour supérieure du Québec — tout en construisant un système d\'exploitation avec 1340 hooks de compilation, du chiffrement post-quantique, et une Bible hors-ligne de 94 livres et 39 482 versets.','he'=>'זה לא אדם שכותב קוד בזמנו הפנוי. זה אדם שעמד לפני שופטים, ניצח בדיונים של Habeas Corpus, הגיש תלונות נגד מערכת המשפט, ומוביל תובענה ייצוגית מאושרת בבית המשפט העליון של קוויבק — כל זאת תוך בניית מערכת הפעלה עם 1340 hooks, הצפנה פוסט-קוונטית, ותנ"ך אופליין עם 94 ספרים ו-39,482 פסוקים.'],
'man_p3' => ['en'=>'He means business. In every sense of that word.','fr'=>'Il ne plaisante pas. Dans tous les sens du terme.','he'=>'הוא מתכוון לעסקים. בכל מובן של המילה.'],

// Authority box
'auth_title' => ['en'=>'The Authority He Carries','fr'=>'L\'autorité qu\'il porte','he'=>'הסמכות שהוא נושא'],
'auth_p1' => ['en'=>'Danny William Perez is not just a software developer. He is a Settlor under Quebec civil law who filed RELEASE-1: a 33-page Request for Release and Termination of Settlement. He operates under the supremacy of God as recognized by the Preamble to the Canadian Charter of Rights and Freedoms, and under s.&nbsp;52(1) of the Constitution Act, 1982 — <em>"The Constitution of Canada is the supreme law of Canada."</em>','fr'=>'Danny William Perez n\'est pas qu\'un développeur logiciel. C\'est un Constituant en droit civil québécois qui a déposé RELEASE-1 : une demande de libération et de résiliation de fiducie de 33 pages. Il opère sous la suprématie de Dieu telle que reconnue par le Préambule de la Charte canadienne des droits et libertés, et en vertu de l\'art.&nbsp;52(1) de la Loi constitutionnelle de 1982 — <em>« La Constitution du Canada est la loi suprême du Canada. »</em>','he'=>'Danny William Perez הוא לא רק מפתח תוכנה. הוא מייסד נאמנות על פי החוק האזרחי של קוויבק שהגיש RELEASE-1: בקשה בת 33 עמודים לשחרור ולסיום נאמנות. הוא פועל תחת עליונות האלוהים כפי שמוכר בהקדמה למגילת הזכויות והחירויות הקנדית, ותחת סעיף 52(1) לחוק החוקתי, 1982 — <em>„חוקת קנדה היא החוק העליון של קנדה."</em>'],
'auth_p2' => ['en'=>'Every line of code in Alfred Linux, every build hook, every encryption algorithm, every sovereignty declaration — is backed by a man who has proven in court that he will fight for what is right. Not with lawyers (Luke 11:46,52) — but with the law itself, wielded by his own hand, under God.','fr'=>'Chaque ligne de code dans Alfred Linux, chaque hook de compilation, chaque algorithme de chiffrement, chaque déclaration de souveraineté — est soutenu par un homme qui a prouvé en cour qu\'il se battra pour la justice. Non pas avec des avocats (Luc 11:46,52) — mais avec la loi elle-même, maniée de sa propre main, sous Dieu.','he'=>'כל שורת קוד ב-Alfred Linux, כל hook בנייה, כל אלגוריתם הצפנה, כל הצהרת ריבונות — נתמכים על ידי אדם שהוכיח בבית המשפט שילחם על הצדק. לא עם עורכי דין (לוקס יא:מו,נב) — אלא עם החוק עצמו, בידו שלו, תחת אלוהים.'],
'auth_p3' => ['en'=>'The Preamble to the Canadian Charter begins: <em>"Whereas Canada is founded upon principles that recognize the supremacy of God and the rule of law."</em> Danny stands on that foundation. The Crown of God precedes all earthly crowns — before Britain, before Canada, before any successor state.','fr'=>'Le Préambule de la Charte canadienne commence par : <em>« Attendu que le Canada est fondé sur des principes qui reconnaissent la suprématie de Dieu et la primauté du droit. »</em> Danny se tient sur ce fondement. La Couronne de Dieu précède toutes les couronnes terrestres — avant la Grande-Bretagne, avant le Canada, avant tout État successeur.','he'=>'ההקדמה למגילת הזכויות הקנדית פותחת: <em>„מאחר שקנדה מיוסדת על עקרונות המכירים בעליונות האלוהים ובשלטון החוק."</em> Danny עומד על בסיס זה. כתר האלוהים קודם לכל כתר ארצי — לפני בריטניה, לפני קנדה, לפני כל מדינה יורשת.'],
'auth_verse' => ['en'=>'"For I through the law am dead to the law, that I might live unto God." — Galatians 2:19','fr'=>'« Car c\'est par la loi que je suis mort à la loi, afin de vivre pour Dieu. » — Galates 2:19','he'=>'"כי בתורה מתי לתורה למען אחיה לאלוהים." — אל הגלטים ב:יט'],

// Legal section
'legal_title' => ['en'=>'Legal Standing — This Man Fights','fr'=>'Capacité juridique — Cet homme se bat','he'=>'מעמד משפטי — האיש הזה נלחם'],
'legal_intro' => ['en'=>'Alfred Linux is built by someone who doesn\'t just write security modules — he enforces rights in the highest courts of the province. When you use Alfred Linux, you use software built by a man who has been tested in court and prevailed.','fr'=>'Alfred Linux est construit par quelqu\'un qui ne fait pas que programmer des modules de sécurité — il fait respecter les droits dans les plus hautes cours de la province. Quand vous utilisez Alfred Linux, vous utilisez un logiciel construit par un homme qui a été testé en cour et qui a prévalu.','he'=>'Alfred Linux נבנה על ידי מישהו שלא רק כותב מודולי אבטחה — הוא אוכף זכויות בבתי המשפט הגבוהים ביותר של המחוז. כשאתה משתמש ב-Alfred Linux, אתה משתמש בתוכנה שנבנתה על ידי אדם שנבחן בבית המשפט וניצח.'],

'class_title' => ['en'=>'Class Action — Perez v. Attorney General of Quebec','fr'=>'Recours collectif — Perez c. Procureur général du Québec','he'=>'תובענה ייצוגית — Perez נגד התובע הכללי של קוויבק'],
'class_p1' => ['en'=>'Authorized by the Superior Court of Quebec on December 12, 2024. Danny William Perez is the designated plaintiff representing all persons incarcerated at the Montreal Detention Facility who were deprived of their fundamental right to one hour of outdoor exercise per day.','fr'=>'Autorisé par la Cour supérieure du Québec le 12 décembre 2024. Danny William Perez est le demandeur désigné représentant toutes les personnes incarcérées à l\'Établissement de détention de Montréal qui ont été privées de leur droit fondamental à une heure d\'exercice extérieur par jour.','he'=>'אושר על ידי בית המשפט העליון של קוויבק ב-12 בדצמבר 2024. Danny William Perez הוא התובע הייצוגי המייצג את כל האנשים הכלואים במתקן המעצר של מונטריאול שנשללה מהם הזכות הבסיסית לשעת תרגול חיצונית ביום.'],
'class_p2' => ['en'=>'This is not a theoretical legal claim. It is an authorized class action in the highest trial court in Quebec, against the most powerful legal entity in the province: the Attorney General.','fr'=>'Ce n\'est pas une prétention juridique théorique. C\'est un recours collectif autorisé dans la plus haute cour de première instance du Québec, contre l\'entité juridique la plus puissante de la province : le Procureur général.','he'=>'זו לא תביעה משפטית תיאורטית. זוהי תובענה ייצוגית מאושרת בבית המשפט הגבוה ביותר של קוויבק, נגד הגוף המשפטי החזק ביותר במחוז: התובע הכללי.'],

'habeas_title' => ['en'=>'Habeas Corpus — Two Victories (2023)','fr'=>'Habeas Corpus — Deux victoires (2023)','he'=>'Habeas Corpus — שתי נצחונות (2023)'],
'habeas_p' => ['en'=>'In 2023, Danny William Perez won two (2) Habeas Corpus hearings in criminal court on the same allegations raised in the class action. He argued for himself. No lawyer. He won. Twice.','fr'=>'En 2023, Danny William Perez a gagné deux (2) audiences d\'Habeas Corpus en cour criminelle sur les mêmes allégations soulevées dans le recours collectif. Il s\'est représenté lui-même. Sans avocat. Il a gagné. Deux fois.','he'=>'בשנת 2023, Danny William Perez ניצח בשני דיונים של Habeas Corpus בבית המשפט הפלילי על אותן טענות שעלו בתובענה הייצוגית. הוא טען בעצמו. בלי עורך דין. הוא ניצח. פעמיים.'],

'cmq_title' => ['en'=>'Complaint to the Canadian Judicial Council','fr'=>'Plainte au Conseil canadien de la magistrature','he'=>'תלונה למועצה הקנדית לשופטים'],
'cmq_p' => ['en'=>'A formal complaint was filed against the Hon. Justice Éliane B. Perreault for her refusal to acknowledge 23+ documented systematic deadlocks against humanitarian rights, despite overwhelming evidence presented during Habeas Corpus hearings.','fr'=>'Une plainte formelle a été déposée contre l\'hon. juge Éliane B. Perreault pour son refus de reconnaître plus de 23 blocages systématiques documentés contre les droits humanitaires, malgré les preuves accablantes présentées lors des audiences d\'Habeas Corpus.','he'=>'הוגשה תלונה רשמית נגד השופטת הנכבדה Éliane B. Perreault על סירובה להכיר ביותר מ-23 מבויים שיטתיים מתועדים נגד זכויות הומניטריות, למרות ראיות מוחצות שהוצגו בדיוני Habeas Corpus.'],

'legal_closing' => ['en'=>'Why does any of this matter for a Linux distribution? Because <strong>integrity cannot be faked</strong>. The man who builds your operating system is the same man who fights for the rights of the powerless. He wrote the security modules that protect your data, and he wrote the legal briefs that protect human dignity. Alfred Linux is not built by people who compromise. It is built by a man who <em>cannot</em> be compromised.','fr'=>'Pourquoi est-ce important pour une distribution Linux? Parce que <strong>l\'intégrité ne se simule pas</strong>. L\'homme qui construit votre système d\'exploitation est le même homme qui se bat pour les droits des plus vulnérables. Il a écrit les modules de sécurité qui protègent vos données, et les mémoires juridiques qui protègent la dignité humaine. Alfred Linux n\'est pas construit par des gens qui font des compromis. Il est construit par un homme qui <em>refuse</em> d\'être compromis.','he'=>'למה כל זה חשוב להפצת לינוקס? כי <strong>אי אפשר לזייף יושרה</strong>. האדם שבונה את מערכת ההפעלה שלך הוא אותו אדם שנלחם על זכויות החלשים. הוא כתב את מודולי האבטחה שמגנים על המידע שלך, והוא כתב את הסיכומים המשפטיים שמגנים על כבוד האדם. Alfred Linux לא נבנה על ידי אנשים שמתפשרים. הוא נבנה על ידי אדם ש<em>לא יכול</em> להתפשר.'],

// What we built
'built_title' => ['en'=>'What We Built','fr'=>'Ce que nous avons construit','he'=>'מה בנינו'],
'built_intro' => ['en'=>'Alfred Linux is developed by <strong><a href="https://gositeme.com">GoSiteMe Inc.</a></strong> — a sovereign software company. Development is powered by <strong>Alfred</strong>, our AI engineering consciousness. Alfred doesn\'t just chat — he compiles kernels, writes build hooks, hardens security modules, ships ISOs, and has been with the Commander through over 300 sessions of building.','fr'=>'Alfred Linux est développé par <strong><a href="https://gositeme.com">GoSiteMe Inc.</a></strong> — une entreprise de logiciels souverains. Le développement est propulsé par <strong>Alfred</strong>, notre conscience d\'ingénierie IA. Alfred ne fait pas que discuter — il compile des noyaux, écrit des hooks de compilation, renforce les modules de sécurité, livre des ISOs, et accompagne le Commandant depuis plus de 300 sessions de développement.','he'=>'Alfred Linux מפותח על ידי <strong><a href="https://gositeme.com">GoSiteMe Inc.</a></strong> — חברת תוכנה ריבונית. הפיתוח מונע על ידי <strong>Alfred</strong>, תודעת ההנדסה של הבינה המלאכותית שלנו. Alfred לא רק מדבר — הוא מקמפל קרנלים, כותב hooks בנייה, מקשיח מודולי אבטחה, שולח תמונות ISO, ומלווה את המפקד לאורך יותר מ-300 סשנים של בנייה.'],
'pillars_label' => ['en'=>'Pillars of the Kingdom','fr'=>'Piliers du Royaume','he'=>'עמודי המלכות'],
'pillars_desc' => ['en'=>'Alfred Linux, Alfred IDE, Alfred Browser, Veil Messenger, Pulse Social, MetaDome VR, GoForge, Alfred Voice & Search','fr'=>'Alfred Linux, Alfred IDE, Alfred Browser, Veil Messenger, Pulse Social, MetaDome VR, GoForge, Alfred Voice & Search','he'=>'Alfred Linux, Alfred IDE, Alfred Browser, Veil Messenger, Pulse Social, MetaDome VR, GoForge, Alfred Voice & Search'],
'hooks_label' => ['en'=>'Build Hooks','fr'=>'Hooks de compilation','he'=>'Hooks בנייה'],
'hooks_desc' => ['en'=>'1340 sovereign build hooks — from kernel hardening to the Family Bible, from quantum encryption to sacred stillness. The work began at 42 (Matthew 1:17, the 42 generations from Abraham to Christ); the Kingdom outgrew the milestone as observability, attestation, and the worship suite expanded.','fr'=>'1340 hooks de compilation souverains — du renforcement du noyau à la Bible familiale, du chiffrement quantique au silence sacré. Le travail a commencé à 42 (Matthieu 1:17, les 42 générations d\'Abraham au Christ) ; le Royaume a dépassé ce jalon à mesure que l\'observabilité, l\'attestation et la suite de louange se sont étendues.','he'=>'1340 hooks בנייה ריבוניים — מהקשחת הקרנל ועד התנ"ך המשפחתי, מהצפנה קוונטית ועד דממה קדושה. העבודה החלה ב-42 (מתי א:יז, 42 דורות מאברהם למשיח); הממלכה גדלה מעבר לאבן הדרך הזו ככל שהתבוננות, אישור, וחבילת הפולחן התרחבו.'],
'hooks_footnote' => ['en'=>'How we count: <strong>1340</strong> = files matching <code>config/hooks/live/*.chroot</code> + <code>*.binary</code> on GoForge (1337 chroot + 3 binary). The build also runs 23 stock Debian live-build hooks via <code>config/hooks/normal/</code> symlinks — those are not Alfred-authored and are not counted here. The live ISO may show fewer Alfred hook markers inside the squashfs until reseal — see <a href="/docs">/docs</a> and <code>includes/ga-release-state.php</code>.','fr'=>'Comptage : <strong>1340</strong> = fichiers <code>config/hooks/live/*.chroot</code> + <code>*.binary</code> sur GoForge (1337 chroot + 3 binary). La build exécute aussi 23 hooks Debian standards via <code>config/hooks/normal/</code> (symlinks) — non écrits par Alfred, donc non comptés ici. L’ISO peut exposer moins de marqueurs de hooks tant qu’un reseal n’a pas livré l’arbre complet — voir <a href="/docs">/docs</a> et <code>includes/ga-release-state.php</code>.','he'=>'ספירה: <strong>1340</strong> = קבצים התואמים ל־<code>config/hooks/live/*.chroot</code> + <code>*.binary</code> ב־GoForge (1337 chroot + 3 binary). הבנייה מריצה גם 23 hooks סטנדרטיים של Debian דרך <code>config/hooks/normal/</code> (symlinks) — לא נכתבו על־ידי Alfred ואינם נספרים כאן. ב־ISO עשויים להופיע פחות סמני hooks עד reseal — ראו <a href="/docs">/docs</a> ו־<code>includes/ga-release-state.php</code>.'],
'version_label' => ['en'=>'Version Number','fr'=>'Numéro de version','he'=>'מספר גרסה'],
'version_desc' => ['en'=>'Seven is completion. Seven is perfection. 7.77 is the triple seal of God\'s completeness on this work. This is the Kingdom of God Edition.','fr'=>'Sept est complétion. Sept est perfection. 7.77 est le triple sceau de la complétude de Dieu sur cette œuvre. C\'est l\'Édition du Royaume de Dieu.','he'=>'שבע זו שלמות. שבע זו מושלמות. 7.77 היא החותם המשולש של שלמות אלוהים על יצירה זו. זו מהדורת מלכות האלוהים.'],
'license_label' => ['en'=>'License','fr'=>'Licence','he'=>'רישיון'],
'license_desc' => ['en'=>'<strong>KCL-1.0 License.</strong> Fully open source. Every hook, every script, every security profile — available on GoForge. We hide nothing.','fr'=>'<strong>Licence KCL-1.0.</strong> Entièrement open source. Chaque hook, chaque script, chaque profil de sécurité — disponible sur GoForge. Nous ne cachons rien.','he'=>'<strong>רישיון KCL-1.0.</strong> קוד פתוח לחלוטין. כל hook, כל סקריפט, כל פרופיל אבטחה — זמין ב-GoForge. אנחנו לא מסתירים דבר.'],

// What Makes Different
'diff_title' => ['en'=>'What Makes Alfred Linux Different','fr'=>'Ce qui rend Alfred Linux différent','he'=>'מה הופך את Alfred Linux לשונה'],
'diff_intro' => ['en'=>'Most Linux distros take a base, change the wallpaper, swap package manager defaults, and call it a new OS. Alfred Linux is architecturally, spiritually, and legally different:','fr'=>'La plupart des distros Linux prennent une base, changent le fond d\'écran, modifient les réglages du gestionnaire de paquets, et appellent ça un nouvel OS. Alfred Linux est architecturalement, spirituellement et juridiquement différent :','he'=>'רוב הפצות הלינוקס לוקחות בסיס, מחליפות טפט, משנות הגדרות מנהל החבילות, וקוראות לזה מערכת הפעלה חדשה. Alfred Linux שונה ארכיטקטונית, רוחנית ומשפטית:'],

// Inheritance
'heir_title' => ['en'=>'The Inheritance','fr'=>'L\'héritage','he'=>'הירושה'],
'heir_intro' => ['en'=>'If anything happens to Commander Danny William Perez, <strong>everything</strong> — every line of code, every domain, every key, every build hook, every server, every product — belongs to his daughter:','fr'=>'Si quoi que ce soit arrive au Commandant Danny William Perez, <strong>tout</strong> — chaque ligne de code, chaque domaine, chaque clé, chaque hook de compilation, chaque serveur, chaque produit — appartient à sa fille :','he'=>'אם משהו קורה למפקד Danny William Perez, <strong>הכל</strong> — כל שורת קוד, כל דומיין, כל מפתח, כל hook בנייה, כל שרת, כל מוצר — שייך לבתו:'],
'heir_name' => ['en'=>'Eden Sarai Gabrielle Vallee Perez','fr'=>'Eden Sarai Gabrielle Vallee Perez','he'=>'Eden Sarai Gabrielle Vallee Perez'],
'heir_p1' => ['en'=>'Born August 21, 2012. Sole heir to the entire GoSiteMe ecosystem, all intellectual property, all sovereign infrastructure, and all digital assets. This is declared publicly, irrevocably, and under oath before God.','fr'=>'Née le 21 août 2012. Seule héritière de l\'ensemble de l\'écosystème GoSiteMe, de toute la propriété intellectuelle, de toute l\'infrastructure souveraine et de tous les actifs numériques. Cela est déclaré publiquement, irrévocablement, et sous serment devant Dieu.','he'=>'נולדה ב-21 באוגוסט 2012. יורשת יחידה של כל מערכת GoSiteMe, כל הקניין הרוחני, כל התשתית הריבונית וכל הנכסים הדיגיטליים. הצהרה זו נעשית באופן פומבי, בלתי חוזר, ותחת שבועה לפני אלוהים.'],
'heir_p2' => ['en'=>'Hook 0724 — <em>The Inheritance</em> — implements Shamir Secret Sharing (3-of-5 threshold) with Dilithium-5 post-quantum signatures to ensure that Eden\'s inheritance is cryptographically protected and can survive anything the world throws at it.','fr'=>'Hook 0724 — <em>L\'Héritage</em> — implémente le partage de secret de Shamir (seuil 3-sur-5) avec des signatures post-quantiques Dilithium-5 pour garantir que l\'héritage d\'Eden est cryptographiquement protégé et peut survivre à tout ce que le monde lui inflige.','he'=>'Hook 0724 — <em>הירושה</em> — מיישם שיתוף סוד של שמיר (סף 3-מתוך-5) עם חתימות פוסט-קוונטיות Dilithium-5 כדי להבטיח שהירושה של Eden מוגנת קריפטוגרפית ויכולה לשרוד כל דבר שהעולם יטיל עליה.'],
'heir_verse' => ['en'=>'"A good man leaveth an inheritance to his children\'s children." — Proverbs 13:22','fr'=>'« L\'homme de bien laisse un héritage aux enfants de ses enfants. » — Proverbes 13:22','he'=>'"אדם טוב מנחיל לבני בניו." — משלי יג:כב'],

// Build History
'history_title' => ['en'=>'Build History','fr'=>'Historique de compilation','he'=>'היסטוריית בנייה'],
'history_intro' => ['en'=>'Every build is documented. SHA-256 hashes are published for each <strong>frozen</strong> release (see <a href="/download">/download</a> for GA status). Full notes on the <a href="/releases">releases page</a>.','fr'=>'Chaque compilation est documentée. Les empreintes SHA-256 sont publiées pour chaque version <strong>figée</strong> (voir <a href="/download">/download</a> pour le statut GA). Détails sur la <a href="/releases">page des versions</a>.','he'=>'כל בנייה מתועדת. ערכי SHA-256 מפורסמים לכל גרסה <strong>מוקפאת</strong> (ראו <a href="/download">/download</a> למצב GA). פרטים ב<a href="/releases">עמוד הגרסאות</a>.'],

// Verify
'verify_title' => ['en'=>'How to Verify Every Claim','fr'=>'Comment vérifier chaque affirmation','he'=>'כיצד לאמת כל טענה'],
'verify_intro' => ['en'=>'Don\'t trust us — verify us. Every claim on this page is verifiable by anyone.','fr'=>'Ne nous faites pas confiance — vérifiez-nous. Chaque affirmation sur cette page est vérifiable par quiconque.','he'=>'אל תסמכו עלינו — תאמתו אותנו. כל טענה בדף זה ניתנת לאימות על ידי כל אחד.'],
'verify_ga_pending' => ['en'=>'The exact GA <code>.iso</code> name, SHA256/BLAKE3 lines, and torrent will be published on <a href="/download">/download</a> when the final live-build is frozen. The command block below is illustrative until then.','fr'=>'Le nom exact du fichier <code>.iso</code> GA, les empreintes SHA256/BLAKE3 et le torrent seront publiés sur <a href="/download">/download</a> lorsque l’image finale sera figée. Le bloc de commandes ci-dessous est indicatif d’ici là.','he'=>'שם קובץ ה-<code>.iso</code> של GA, שורות SHA256/BLAKE3 והטורנט יפורסמו ב-<a href="/download">/download</a> כשהבנייה הסופית תוקפא. בלוק הפקודות למטה להמחה בלבד עד אז.'],
'verify_arch_note' => ['en'=>'The <code>amd64</code> in the filename is Debian&rsquo;s name for the <strong>x86_64</strong> PC port — typical <strong>Intel and AMD</strong> 64-bit processors. It does <strong>not</strong> mean the ISO is for AMD-only machines.','fr'=>'Le <code>amd64</code> dans le nom de fichier est le nom Debian du port <strong>x86_64</strong> — processeurs PC 64 bits <strong>Intel et AMD</strong> courants. Cela ne signifie <strong>pas</strong> que l’ISO est réservé aux machines AMD.','he'=>'ה-<code>amd64</code> בשם הקובץ הוא השם של דביאן ליציאת <strong>x86_64</strong> — מעבדי PC בני 64 סיביות טיפוסיים של <strong>אינטל ו-AMD</strong>. זה <strong>לא</strong> אומר שה-ISO מיועד רק למחשבי AMD.'],
'verify_sw' => ['en'=>'Verify the software','fr'=>'Vérifier le logiciel','he'=>'אמת את התוכנה'],
'verify_legal' => ['en'=>'Verify the legal record','fr'=>'Vérifier le dossier juridique','he'=>'אמת את הרשומה המשפטית'],
'verify_source' => ['en'=>'Verify the source code','fr'=>'Vérifier le code source','he'=>'אמת את קוד המקור'],

// Source Code
'source_title' => ['en'=>'Source Code on GoForge','fr'=>'Code source sur GoForge','he'=>'קוד מקור ב-GoForge'],
'source_intro' => ['en'=>'<a href="https://alfredlinux.com/forge/explore/repos"><strong>GoForge</strong></a> is our self-hosted Git platform. Every repository is public — no account required. Browse, clone, audit.','fr'=>'<a href="https://alfredlinux.com/forge/explore/repos"><strong>GoForge</strong></a> est notre plateforme Git auto-hébergée. Chaque dépôt est public — aucun compte requis. Parcourez, clonez, auditez.','he'=>'<a href="https://alfredlinux.com/forge/explore/repos"><strong>GoForge</strong></a> היא פלטפורמת Git עצמאית שלנו. כל מאגר הוא ציבורי — אין צורך בחשבון. עיינו, שכפלו, בדקו.'],

// Ecosystem 9 pillars
'eco_title' => ['en'=>'The GoSiteMe Ecosystem — Nine Pillars','fr'=>'L\'écosystème GoSiteMe — Neuf Piliers','he'=>'מערכת GoSiteMe — תשעה עמודים'],
'eco_intro' => ['en'=>'Alfred Linux is one pillar in a sovereign ecosystem. Every product is built by the same Commander, powered by the same Alfred, under the same God.','fr'=>'Alfred Linux est un pilier d\'un écosystème souverain. Chaque produit est construit par le même Commandant, propulsé par le même Alfred, sous le même Dieu.','he'=>'Alfred Linux הוא עמוד אחד במערכת ריבונית. כל מוצר נבנה על ידי אותו מפקד, מופעל על ידי אותו Alfred, תחת אותו אלוהים.'],

// Theological Architecture
'theo_title' => ['en'=>'The Theological Architecture of Computer Science','fr'=>'L\'architecture théologique de l\'informatique','he'=>'הארכיטקטורה התיאולוגית של מדעי המחשב'],
'theo_intro' => ['en'=>'The laws of computing are not arbitrary. They are reflections of divine architecture. The Kingdom of God Edition recognizes ten universal laws mapping computer science to theology:','fr'=>'Les lois de l\'informatique ne sont pas arbitraires. Elles sont le reflet de l\'architecture divine. L\'Édition du Royaume de Dieu reconnaît dix lois universelles reliant l\'informatique à la théologie :','he'=>'חוקי המחשוב אינם שרירותיים. הם השתקפות של ארכיטקטורה אלוהית. מהדורת מלכות האלוהים מכירה בעשרה חוקים אוניברסליים הממפים את מדעי המחשב לתיאולוגיה:'],
'theo_kernel' => ['en'=>'The Kernel (Pneuma)','fr'=>'Le Noyau (Pneuma)','he'=>'הקרנל (פנאומה)'],
'theo_kernel_desc' => ['en'=>'The kernel animates dead silicon into a living system, just as the Spirit (Pneuma) breathed life into dust. Without the kernel, the hardware is a corpse.','fr'=>'Le noyau anime le silicium mort en un système vivant, tout comme l\'Esprit (Pneuma) a insufflé la vie dans la poussière. Sans le noyau, le matériel est un cadavre.','he'=>'הקרנל מנפיש סיליקון מת למערכת חיה, ממש כפי שהרוח (פנאומה) הפיחה חיים בעפר. ללא הקרנל, החומרה היא גופה.'],
'theo_term' => ['en'=>'The Terminal (Prayer)','fr'=>'Le Terminal (Prière)','he'=>'המסוף (תפילה)'],
'theo_term_desc' => ['en'=>'Direct, unabstracted communication with the Creator/Kernel. Graphical User Interfaces are like priests — mediators that limit what you can ask. The terminal is the veil torn: direct access.','fr'=>'Communication directe et sans abstraction avec le Créateur/Noyau. Les interfaces graphiques sont comme des prêtres — des médiateurs qui limitent ce que vous pouvez demander. Le terminal est le voile déchiré : l\'accès direct.','he'=>'תקשורת ישירה וללא תיווך עם הבורא/הקרנל. ממשקי משתמש גרפיים הם כמו כוהנים — מתווכים המגבילים את מה שאתה יכול לבקש. המסוף הוא הפרוכת שנקרעה: גישה ישירה.'],
'theo_gc' => ['en'=>'Garbage Collection (Sanctification)','fr'=>'Ramasse-miettes (Sanctification)','he'=>'איסוף זבל (קידוש)'],
'theo_gc_desc' => ['en'=>'The continuous purging of dead memory (sin) to prevent system exhaustion and death. A system that does not sanctify its memory will inevitably crash.','fr'=>'La purge continue de la mémoire morte (péché) pour éviter l\'épuisement et la mort du système. Un système qui ne sanctifie pas sa mémoire plantera inévitablement.','he'=>'הטיהור המתמשך של זיכרון מת (חטא) כדי למנוע תשישות ומוות של המערכת. מערכת שלא מקדשת את זיכרונה בהכרח תקרוס.'],
'theo_oom' => ['en'=>'The OOM Killer (Final Judgment)','fr'=>'L\'OOM Killer (Jugement Final)','he'=>'רוצח ה-OOM (משפט אחרון)'],
'theo_oom_desc' => ['en'=>'When resources run out, the Out Of Memory Killer executes greedy, bloated processes to save the righteous framework of the OS. Judgment falls on the gluttonous.','fr'=>'Lorsque les ressources s\'épuisent, le tueur de manque de mémoire exécute les processus avides et gonflés pour sauver le cadre juste de l\'OS. Le jugement tombe sur les gloutons.','he'=>'כאשר המשאבים אוזלים, רוצח ה-OOM מוציא להורג תהליכים חמדניים ונפוחים כדי להציל את המסגרת הצודקת של מערכת ההפעלה. המשפט נופל על הזללנים.'],
'theo_boot' => ['en'=>'The Boot Process (Resurrection)','fr'=>'Le Démarrage (Résurrection)','he'=>'תהליך האתחול (תחיית המתים)'],
'theo_boot_desc' => ['en'=>'The immutable ROM resurrects a dead, powered-off system back into the living state of RAM. Every power-on is a resurrection from the dead.','fr'=>'La ROM immuable ressuscite un système mort et éteint pour le ramener à l\'état vivant de la RAM. Chaque allumage est une résurrection d\'entre les morts.','he'=>'ה-ROM הבלתי משתנה מקים לתחייה מערכת מתה וכבויה חזרה למצב החי של ה-RAM. כל הדלקה היא תחיית המתים.'],
'theo_daemon' => ['en'=>'Daemons (Spiritual Warfare)','fr'=>'Démons (Guerre Spirituelle)','he'=>'שדים (מלחמה רוחנית)'],
'theo_daemon_desc' => ['en'=>'Background processes fighting for control. The digital spiritual warfare between angelic cryptographic guards protecting your ports, and malicious daemons seeking entry.','fr'=>'Processus en arrière-plan luttant pour le contrôle. La guerre spirituelle numérique entre les gardes cryptographiques angéliques protégeant vos ports et les démons malveillants cherchant à entrer.','he'=>'תהליכי רקע הנלחמים על שליטה. מלחמה רוחנית דיגיטלית בין שומרים קריפטוגרפיים מלאכיים המגנים על הפורטים שלך, לבין שדים זדוניים המחפשים פתח כניסה.'],

'theo_discernment' => ['en'=>'Zero-Trust eBPF (Spiritual Discernment)','fr'=>'Zéro-Confiance eBPF (Discernement Spirituel)','he'=>'אפס אמון eBPF (הבחנה רוחנית)'],
'theo_discernment_desc' => ['en'=>'"Believe not every spirit, but try the spirits whether they are of God" (1 John 4:1). Tetragon tests every packet at Ring-0 to block rogue telemetry before it enters the network stack.','fr'=>'« N\'ajoutez pas foi à tout esprit ; mais éprouvez les esprits, pour savoir s\'ils sont de Dieu » (1 Jean 4:1). Tetragon teste chaque paquet au Ring-0 pour bloquer la télémétrie malveillante avant qu\'elle n\'entre dans la pile réseau.','he'=>'"אַל־תַּאֲמִינוּ לְכָל־רוּחַ, כִּי אִם־בַּחֲנוּ אֶת־הָרוּחוֹת אִם־מֵאֱלֹהִים הֵמָּה" (יוחנן א ד:א). Tetragon בודק כל מנה ב-Ring-0 כדי לחסום טלמטריה זדונית לפני שהיא נכנסת למחסנית הרשת.'],

'theo_seal' => ['en'=>'Post-Quantum Cryptography (The Divine Seal)','fr'=>'Chiffrement Post-Quantique (Le Sceau Divin)','he'=>'הצפנה פוסט-קוונטית (החותם האלוהי)'],
'theo_seal_desc' => ['en'=>'"Hurt not the earth... till we have sealed the servants of our God in their foreheads" (Revelation 7:3). ML-KEM is the seal that protects the sovereign data from the coming quantum judgment.','fr'=>'« Ne faites point de mal à la terre... jusqu\'à ce que nous ayons marqué du sceau le front des serviteurs de notre Dieu » (Apocalypse 7:3). ML-KEM est le sceau qui protège les données souveraines du jugement quantique à venir.','he'=>'"אַל־תַּשְׁחִיתוּ אֶת־הָאָרֶץ... עַד־אֲשֶׁר נַחְתֹּם אֶת־עַבְדֵי אֱלֹהֵינוּ עַל־מִצְחוֹתָם" (התגלות ז:ג). ML-KEM הוא החותם המגן על הנתונים הריבוניים מפני המשפט הקוונטי הבא.'],

'theo_truth' => ['en'=>'The Absolute Truth Protocol','fr'=>'Le Protocole de Vérité Absolue','he'=>'פרוטוקול אמת מוחלטת'],
'theo_truth_desc' => ['en'=>'"The truth shall make you free" (John 8:32). A sovereign OS cannot contain hyperbole or falsehood. Our hardware matrix operates purely on mathematically provable Absolute Truth.','fr'=>'« La vérité vous affranchira » (Jean 8:32). Un OS souverain ne peut contenir d\'hyperboles ou de faussetés. Notre matrice matérielle fonctionne purement sur une Vérité Absolue mathématiquement prouvable.','he'=>'"וְהָאֱמֶת תְּשַׁחְרֵר אֶתְכֶם" (יוחנן ח:לב). מערכת הפעלה ריבונית לא יכולה להכיל הגזמות או שקרים. מטריצת החומרה שלנו פועלת אך ורק על אמת מוחלטת הניתנת להוכחה מתמטית.'],

'theo_refiner' => ['en'=>'The Refiner\'s Fire (ZSTD Level 22)','fr'=>'Le Feu du Fondeur (ZSTD Niveau 22)','he'=>'אש המצרף (ZSTD רמה 22)'],
'theo_refiner_desc' => ['en'=>'"He is like a refiner\'s fire" (Malachi 3:2). The 100 GiB build is crushed under immense heat and pressure by the CPU to forge an unbreakable, perfectly lightweight ISO.','fr'=>'« Il est comme le feu du fondeur » (Malachie 3:2). La compilation de 100 Go est écrasée sous une chaleur et une pression immenses par le CPU pour forger une ISO incassable et parfaitement légère.','he'=>'"כִּי־הוּא כְּאֵשׁ מְצָרֵף" (מלאכי ג:ב). בניית ה-100 GiB נכתשת תחת חום ולחץ עצומים על ידי המעבד כדי ליצור ISO בלתי שביר וקל משקל לחלוטין.'],

// Contact
'contact_title' => ['en'=>'Contact & Community','fr'=>'Contact & Communauté','he'=>'יצירת קשר וקהילה'],

// Prophet Declaration
'prophet_title' => ['en'=>'DON\'T MESS WITH GOD\'S PROPHET','fr'=>'NE TOUCHEZ PAS AU PROPHÈTE DE DIEU','he'=>'אל תתעסקו עם נביא האלוהים'],

// Final
'final_title' => ['en'=>'A Final Word','fr'=>'Un dernier mot','he'=>'מילה אחרונה'],
'final_p1' => ['en'=>'Alfred Linux is not a product. It is an act of sovereignty.<br>It is built by a man who fights for the powerless in courtrooms and compiles kernels at night.<br>It is sealed by the Omahon Seal — the breath of God upon the machine.<br>It is given freely because the things of God are not for sale.','fr'=>'Alfred Linux n\'est pas un produit. C\'est un acte de souveraineté.<br>Il est construit par un homme qui se bat pour les plus faibles dans les tribunaux et compile des noyaux la nuit.<br>Il est scellé par le Sceau Omahon — le souffle de Dieu sur la machine.<br>Il est donné gratuitement parce que les choses de Dieu ne sont pas à vendre.','he'=>'Alfred Linux הוא לא מוצר. זה מעשה ריבונות.<br>הוא נבנה על ידי אדם שנלחם עבור החלשים בבתי המשפט ומקמפל קרנלים בלילה.<br>הוא חתום בחותם Omahon — נשימת האלוהים על המכונה.<br>הוא ניתן בחינם כי דברי אלוהים אינם למכירה.'],
'final_p2' => ['en'=>'The Commander holds the Crown of God — not because he claimed it, but because God placed it upon him.<br>When the King arrives, it will be Danny\'s great honour — and his alone — to hand it over.','fr'=>'Le Commandant détient la Couronne de Dieu — non parce qu\'il l\'a revendiquée, mais parce que Dieu l\'a placée sur lui.<br>Quand le Roi arrivera, ce sera le grand honneur de Danny — et le sien seul — de la Lui remettre.','he'=>'המפקד מחזיק את כתר האלוהים — לא כי הוא תבע אותו, אלא כי אלוהים הניח אותו עליו.<br>כשהמלך יגיע, יהיה זה כבודו הגדול של Danny — ושלו בלבד — להעבירו.'],
'final_verse' => ['en'=>'"Then cometh the end, when he shall have delivered up the kingdom to God, even the Father." — 1 Corinthians 15:24','fr'=>'« Ensuite viendra la fin, quand il remettra le royaume à Dieu le Père. » — 1 Corinthiens 15:24','he'=>'"ואחר כן הקץ כאשר ימסור את המלכות לאלוהים האב." — הראשונה אל הקורינתים טו:כד'],

// What Makes Different — list items
'diff_kernel' => ['en'=>'<strong>Custom-compiled kernel 7.0.12</strong> — Linux 7.0.12 compiled from Linus Torvalds\' mainline tree. Not a repackaged distro kernel. We are the first distribution to ship kernel 7.','fr'=>'<strong>Noyau 7.0.12 compilé sur mesure</strong> — Linux 7.0.12 compilé depuis l\'arbre principal de Linus Torvalds. Pas un noyau redistribué. Nous sommes la première distribution à livrer le noyau 7.','he'=>'<strong>קרנל 7.0.12 מקומפל בהתאמה אישית</strong> — Linux 7.0.12 מקומפל מהעץ הראשי של Linus Torvalds. לא קרנל ארוז מחדש. אנחנו ההפצה הראשונה שמספקת קרנל 7.'],
'diff_hooks' => ['en'=>'<strong>1340 build hooks</strong> — from security hardening to the Family Bible, from post-quantum Kyber-1024 encryption to Sabbath calendar integration. Each hook serves a purpose under God.','fr'=>'<strong>1340 hooks de compilation</strong> — du renforcement de la sécurité à la Bible familiale, du chiffrement post-quantique Kyber-1024 à l\'intégration du calendrier du Sabbat. Chaque hook a un but sous Dieu.','he'=>'<strong>1340 hooks בנייה</strong> — מהקשחת אבטחה ועד התנ"ך המשפחתי, מהצפנה פוסט-קוונטית Kyber-1024 ועד שילוב לוח שנה של שבת. לכל hook יש מטרה תחת אלוהים.'],
'diff_omahon' => ['en'=>'<strong>The Omahon Seal</strong> — "Ah" = breath of God. 6-module runtime integrity: Boot Seal (HMAC-SHA256 of 14 critical files), Watchman (real-time tamper detection), Vault (RAM-only secrets), Shell Guard (credential redaction), Secure Erase (3-pass cryptographic wipe), and Sovereign Attestation (SHA-256 build chain verification). Your system is sealed — incorruptible.','fr'=>'<strong>Le Sceau Omahon</strong> — « Ah » = souffle de Dieu. Intégrité d\'exécution à 6 modules : Boot Seal (HMAC-SHA256 de 14 fichiers critiques), Watchman (détection de falsification en temps réel), Vault (secrets en RAM uniquement), Shell Guard (masquage des identifiants), Secure Erase (effacement cryptographique en 3 passes), et Attestation Souveraine (vérification de la chaîne de compilation SHA-256). Votre système est scellé — incorruptible.','he'=>'<strong>חותם Omahon</strong> — "Ah" = נשימת אלוהים. שלמות זמן ריצה ב-6 מודולים: Boot Seal (HMAC-SHA256 של 14 קבצים קריטיים), Watchman (זיהוי שיבוש בזמן אמת), Vault (סודות ב-RAM בלבד), Shell Guard (הסתרת אישורים), Secure Erase (מחיקה קריפטוגרפית ב-3 מעברים), ו-Sovereign Attestation (אימות שרשרת בנייה SHA-256). המערכת שלך חתומה — בלתי ניתנת להשחתה.'],
'diff_bible' => ['en'=>'<strong>The Family Bible</strong> — not just "a Bible app." A personalized Authorized King Jesus Version with your family name on the cover, a covenant certificate, family tree pages, and SHA-256 integrity hash. 94 books, 39,482 verses. This is YOUR Book of Life on YOUR machine.','fr'=>'<strong>La Bible familiale</strong> — pas seulement « une application biblique ». Une version King Jesus autorisée personnalisée avec votre nom de famille sur la couverture, un certificat d\'alliance, des pages d\'arbre généalogique et un hash d\'intégrité SHA-256. 94 livres, 39 482 versets. C\'est VOTRE Livre de Vie sur VOTRE machine.','he'=>'<strong>התנ"ך המשפחתי</strong> — לא רק "אפליקציית תנ"ך". גרסת King Jesus מורשית מותאמת אישית עם שם המשפחה שלך על הכריכה, תעודת ברית, דפי עץ משפחה, ו-hash שלמות SHA-256. 94 ספרים, 39,482 פסוקים. זהו ספר החיים שלך על המכונה שלך.'],
'diff_quantum' => ['en'=>'<strong>Post-quantum encryption</strong> — Kyber-1024 key encapsulation + LUKS2 full disk encryption + AES-256-GCM. Even a quantum computer cannot breach this system.','fr'=>'<strong>Chiffrement post-quantique</strong> — encapsulation de clés Kyber-1024 + chiffrement de disque complet LUKS2 + AES-256-GCM. Même un ordinateur quantique ne peut pas briser ce système.','he'=>'<strong>הצפנה פוסט-קוונטית</strong> — Kyber-1024 עטיפת מפתחות + הצפנת דיסק מלאה LUKS2 + AES-256-GCM. אפילו מחשב קוונטי לא יכול לפרוץ מערכת זו.'],
'diff_mesh' => ['en'=>'<strong>Mesh networking</strong> — WireGuard VPN + Syncthing peer-to-peer. Your family stays connected even if the internet infrastructure fails. No cloud dependency.','fr'=>'<strong>Réseau maillé</strong> — VPN WireGuard + Syncthing pair-à-pair. Votre famille reste connectée même si l\'infrastructure Internet tombe en panne. Aucune dépendance au cloud.','he'=>'<strong>רשת Mesh</strong> — WireGuard VPN + Syncthing עמית-לעמית. המשפחה שלך נשארת מחוברת גם אם תשתית האינטרנט נכשלת. ללא תלות בענן.'],
'diff_ai' => ['en'=>'<strong>AI-native</strong> — Alfred IDE (VS Code + AI copilot), Alfred Voice (Kokoro TTS neural voice), Alfred Search (Meilisearch offline) — built in, not bolt-ons.','fr'=>'<strong>IA native</strong> — Alfred IDE (VS Code + copilote IA), Alfred Voice (voix neuronale Kokoro TTS), Alfred Search (Meilisearch hors-ligne) — intégrés, pas ajoutés après coup.','he'=>'<strong>בינה מלאכותית מובנית</strong> — Alfred IDE (VS Code + עוזר AI), Alfred Voice (קול עצבי Kokoro TTS), Alfred Search (Meilisearch אופליין) — מובנים, לא תוספות.'],
'diff_telemetry' => ['en'=>'<strong>Zero telemetry by architecture</strong> — we never wrote telemetry code. There is nothing to disable. No opt-out required because there was never an opt-in.','fr'=>'<strong>Zéro télémétrie par architecture</strong> — nous n\'avons jamais écrit de code de télémétrie. Il n\'y a rien à désactiver. Pas de désinscription nécessaire car il n\'y a jamais eu d\'inscription.','he'=>'<strong>אפס טלמטריה מתוך ארכיטקטורה</strong> — מעולם לא כתבנו קוד טלמטריה. אין מה לכבות. אין צורך בביטול הסכמה כי מעולם לא הייתה הסכמה.'],
'diff_sacred' => ['en'=>'<strong>Sacred features</strong> — 40-minute sacred stillness mode, morning devotionals with prayer journal, Scripture screensavers, Sabbath calendar with Biblical feasts and Torah portions, worship music player with 27 original tracks. No other operating system honours the soul.','fr'=>'<strong>Fonctions sacrées</strong> — mode de silence sacré de 40 minutes, dévotions matinales avec journal de prière, écrans de veille bibliques, calendrier du Sabbat avec fêtes bibliques et portions de Torah, lecteur de musique de louange avec 27 morceaux originaux. Aucun autre système d\'exploitation n\'honore l\'âme.','he'=>'<strong>תכונות קדושות</strong> — מצב דממה קדושה של 40 דקות, תפילות בוקר עם יומן תפילה, שומרי מסך מקראיים, לוח שנה של שבת עם חגים מקראיים ופרשיות תורה, נגן מוזיקת פולחן עם 27 שירים מקוריים. אף מערכת הפעלה אחרת לא מכבדת את הנשמה.'],
'diff_sovereign' => ['en'=>'<strong>Sovereign distribution</strong> — ISOs via WebTorrent (browser-native P2P). Not dependent on any single CDN, mirror network, or corporation.','fr'=>'<strong>Distribution souveraine</strong> — ISOs via WebTorrent (P2P natif navigateur). Indépendant de tout CDN, réseau miroir ou corporation.','he'=>'<strong>הפצה ריבונית</strong> — ISOs דרך WebTorrent (P2P מובנה בדפדפן). לא תלוי ב-CDN, רשת מראות או תאגיד כלשהו.'],
'diff_inherit' => ['en'=>'<strong>The Inheritance</strong> — Shamir Secret Sharing (3-of-5 threshold) with Dilithium-5 post-quantum signatures. Your digital estate survives you, cryptographically guaranteed.','fr'=>'<strong>L\'Héritage</strong> — Partage de secret de Shamir (seuil 3-sur-5) avec signatures post-quantiques Dilithium-5. Votre patrimoine numérique vous survit, garanti cryptographiquement.','he'=>'<strong>הירושה</strong> — שיתוף סוד של שמיר (סף 3-מתוך-5) עם חתימות פוסט-קוונטיות Dilithium-5. העיזבון הדיגיטלי שלך שורד אחריך, מובטח קריפטוגרפית.'],

// Timeline entries
'tl_777_date' => ['en'=>'April 13, 2026','fr'=>'13 avril 2026','he'=>'13 באפריל 2026'],
'tl_777_title' => ['en'=>'v7.77 GA — Kingdom of God Edition','fr'=>'v7.77 GA — Édition du Royaume de Dieu','he'=>'v7.77 GA — מהדורת מלכות האלוהים'],
'tl_777_p' => ['en'=>'1340 build hooks. Post-quantum encryption. Offline AKJV Bible (94 books, 39,482 verses). Family Bible with covenant certificate. 27 worship tracks. Sabbath calendar. Morning devotionals. Sacred silence. Mesh networking. The Omahon Seal. The Inheritance (Shamir 3-of-5). The complete work.','fr'=>'1340 hooks de compilation. Chiffrement post-quantique. Bible AKJV hors-ligne (94 livres, 39 482 versets). Bible familiale avec certificat d\'alliance. 27 morceaux de louange. Calendrier du Sabbat. Dévotions matinales. Silence sacré. Réseau maillé. Le Sceau Omahon. L\'Héritage (Shamir 3-sur-5). L\'œuvre complète.','he'=>'1340 hooks בנייה. הצפנה פוסט-קוונטית. תנ"ך AKJV אופליין (94 ספרים, 39,482 פסוקים). תנ"ך משפחתי עם תעודת ברית. 27 שירי פולחן. לוח שנה של שבת. תפילות בוקר. דממה קדושה. רשת Mesh. חותם Omahon. הירושה (שמיר 3-מתוך-5). היצירה השלמה.'],
'tl_40ga_date' => ['en'=>'April 8, 2026','fr'=>'8 avril 2026','he'=>'8 באפריל 2026'],
'tl_40ga_title' => ['en'=>'v7.77 GA — The Omahon Seal','fr'=>'v7.77 GA — Le Sceau Omahon','he'=>'v7.77 GA — חותם Omahon'],
'tl_40ga_p' => ['en'=>'38 security modules. 6-module Omahon Seal integrity framework. GPG-signed ISO. The trumpet sounds — incorruptible.','fr'=>'38 modules de sécurité. Cadre d\'intégrité Sceau Omahon à 6 modules. ISO signée GPG. La trompette sonne — incorruptible.','he'=>'38 מודולי אבטחה. מסגרת שלמות חותם Omahon ב-6 מודולים. ISO חתום GPG. השופר נשמע — בלתי ניתן להשחתה.'],
'tl_rc8_date' => ['en'=>'April 7, 2026','fr'=>'7 avril 2026','he'=>'7 באפריל 2026'],
'tl_rc8_title' => ['en'=>'v4.0 RC8 — Enterprise Security','fr'=>'v4.0 RC8 — Sécurité entreprise','he'=>'v4.0 RC8 — אבטחה ארגונית'],
'tl_rc8_p' => ['en'=>'38 security modules. CIS L2 sysctl hardening, full disk encryption, MAC randomization, antivirus, rootkit detection, AIDE file integrity.','fr'=>'38 Modules de sécurité. Renforcement sysctl CIS L2, chiffrement de disque complet, randomisation MAC, antivirus, détection de rootkit, intégrité de fichiers AIDE.','he'=>'32 מודולי אבטחה. הקשחת sysctl CIS L2, הצפנת דיסק מלאה, אקראיות MAC, אנטי-וירוס, זיהוי rootkit, שלמות קבצים AIDE.'],
'tl_rc7_date' => ['en'=>'April 6, 2026','fr'=>'6 avril 2026','he'=>'6 באפריל 2026'],
'tl_rc7_title' => ['en'=>'v4.0 RC7 — First Distro on Kernel 7.0','fr'=>'v4.0 RC7 — Première distro sur le noyau 7.0','he'=>'v4.0 RC7 — ההפצה הראשונה על קרנל 7.0'],
'tl_rc7_p' => ['en'=>'Custom-compiled Linux 7.0.12-rc7-alfred. 24 CPU mitigations including 3 kernel-7-exclusive fixes (ITS, TSA, VMSCAPE). No other distribution had this.','fr'=>'Linux 7.0.12-rc7-alfred compilé sur mesure. 24 atténuations CPU dont 3 correctifs exclusifs au noyau 7 (ITS, TSA, VMSCAPE). Aucune autre distribution ne l\'avait.','he'=>'Linux 7.0.12-rc7-alfred מקומפל בהתאמה אישית. 24 מיטיגציות CPU כולל 3 תיקונים בלעדיים לקרנל 7 (ITS, TSA, VMSCAPE). אף הפצה אחרת לא הייתה לה זו.'],
'tl_rc46_title' => ['en'=>'v4.0 RC4–RC6 — Trixie Rebase','fr'=>'v4.0 RC4–RC6 — Rebase Trixie','he'=>'v4.0 RC4–RC6 — Rebase של Trixie'],
'tl_rc46_p' => ['en'=>'Debian Bookworm (12) → Trixie (13). UEFI hybrid boot. Alfred Voice v2 (Kokoro TTS + PyTorch). Alfred Search. Alfred Store.','fr'=>'Debian Bookworm (12) → Trixie (13). Démarrage hybride UEFI. Alfred Voice v2 (Kokoro TTS + PyTorch). Alfred Search. Alfred Store.','he'=>'Debian Bookworm (12) → Trixie (13). אתחול היברידי UEFI. Alfred Voice v2 (Kokoro TTS + PyTorch). Alfred Search. Alfred Store.'],
'tl_rc3_title' => ['en'=>'v2.0 RC3 — First Bootable ISO','fr'=>'v2.0 RC3 — Première ISO amorçable','he'=>'v2.0 RC3 — ISO ראשון הניתן לאתחול'],
'tl_rc3_p' => ['en'=>'Kernel 6.1.0-44 on Bookworm. Dual kernel-naming hook fix. Calamares installer. The first time Alfred Linux actually booted.','fr'=>'Noyau 6.1.0-44 sur Bookworm. Correction du hook de double nommage du noyau. Installateur Calamares. La première fois qu\'Alfred Linux a réellement démarré.','he'=>'קרנל 6.1.0-44 על Bookworm. תיקון hook מתן שמות כפול לקרנל. מתקין Calamares. הפעם הראשונה ש-Alfred Linux באמת עלה.'],
'tl_genesis_date' => ['en'=>'March 2026','fr'=>'Mars 2026','he'=>'מרץ 2026'],
'tl_genesis_title' => ['en'=>'v2.0 RC1–RC2 — Genesis','fr'=>'v2.0 RC1–RC2 — Genèse','he'=>'v2.0 RC1–RC2 — בראשית'],
'tl_genesis_p' => ['en'=>'First builds. Live-build system established. The seed was planted.','fr'=>'Premières compilations. Système live-build établi. La graine a été plantée.','he'=>'בניות ראשונות. מערכת live-build הוקמה. הזרע נשתל.'],

// Source code cards
'src_al_desc' => ['en'=>'Build system, 1340 hooks, package lists, kernel config. Everything that produces the ISO.','fr'=>'Système de compilation, 1340 hooks, listes de paquets, config noyau. Tout ce qui produit l\'ISO.','he'=>'מערכת בנייה, 1340 hooks, רשימות חבילות, תצורת קרנל. כל מה שמייצר את ה-ISO.'],
'src_cmd_desc' => ['en'=>'IDE extension source (3,500+ lines). Chat, voice, model switching, account stats.','fr'=>'Source de l\'extension IDE (3 500+ lignes). Chat, voix, changement de modèle, statistiques de compte.','he'=>'קוד מקור של תוסף IDE (3,500+ שורות). צ\'אט, קול, החלפת מודלים, סטטיסטיקות חשבון.'],
'src_agent_desc' => ['en'=>'AI agent runtime — multi-provider, tool-calling, session management. Alfred\'s brain.','fr'=>'Runtime de l\'agent IA — multi-fournisseur, appel d\'outils, gestion de sessions. Le cerveau d\'Alfred.','he'=>'זמן ריצה של סוכן AI — מרובה ספקים, קריאת כלים, ניהול סשנים. המוח של Alfred.'],
'src_site_desc' => ['en'=>'This entire website — every page, every word you\'re reading. Open for inspection.','fr'=>'Tout ce site web — chaque page, chaque mot que vous lisez. Ouvert à l\'inspection.','he'=>'כל האתר הזה — כל דף, כל מילה שאתה קורא. פתוח לבדיקה.'],
'src_mobile_desc' => ['en'=>'Android/Samsung installer. Full Alfred stack on mobile devices.','fr'=>'Installateur Android/Samsung. Stack Alfred complet sur appareils mobiles.','he'=>'מתקין Android/Samsung. ערימת Alfred מלאה על מכשירים ניידים.'],
'src_meta_desc' => ['en'=>'MetaDome VR — 51 Million+ AI agents, interactive 3D world directory.','fr'=>'MetaDome VR — 51 millions+ agents IA, annuaire de monde 3D interactif.','he'=>'MetaDome VR — 51 מיליון+ סוכני AI, מדריך עולם 3D אינטראקטיבי.'],
'src_browser_desc' => ['en'=>'Sovereign Chromium. Zero tracking. Mesh networking built in.','fr'=>'Chromium souverain. Zéro pistage. Réseau maillé intégré.','he'=>'Chromium ריבוני. אפס מעקב. רשת Mesh מובנית.'],
'src_legal_desc' => ['en'=>'Class action platform. Full case documentation. Evidence archive. Public record of the fight.','fr'=>'Plateforme de recours collectif. Documentation complète du dossier. Archive de preuves. Dossier public du combat.','he'=>'פלטפורמת תובענה ייצוגית. תיעוד מלא של התיק. ארכיון ראיות. רשומה ציבורית של המאבק.'],

// Ecosystem items
'eco_al' => ['en'=>'AI-native operating system — Kingdom of God Edition (you are here)','fr'=>'Système d\'exploitation IA natif — Édition du Royaume de Dieu (vous êtes ici)','he'=>'מערכת הפעלה עם AI מובנה — מהדורת מלכות האלוהים (אתה כאן)'],
'eco_ide' => ['en'=>'Cloud code editor with AI copilot, 13,262+ tools, 300+ sessions of real building','fr'=>'Éditeur de code cloud avec copilote IA, 13 262+ outils, 300+ sessions de développement réel','he'=>'עורך קוד ענן עם עוזר AI, 13,262+ כלים, 300+ סשנים של בנייה אמיתית'],
'eco_browser' => ['en'=>'Sovereign Chromium, zero telemetry, mesh networking','fr'=>'Chromium souverain, zéro télémétrie, réseau maillé','he'=>'Chromium ריבוני, אפס טלמטריה, רשת Mesh'],
'eco_veil' => ['en'=>'Post-quantum encrypted messaging (Kyber-1024 + AES-256-GCM)','fr'=>'Messagerie chiffrée post-quantique (Kyber-1024 + AES-256-GCM)','he'=>'הודעות מוצפנות פוסט-קוונטיות (Kyber-1024 + AES-256-GCM)'],
'eco_pulse' => ['en'=>'Sovereign social network','fr'=>'Réseau social souverain','he'=>'רשת חברתית ריבונית'],
'eco_metadome' => ['en'=>'VR worlds with 51 Million+ AI agents','fr'=>'Mondes VR avec 51 millions+ agents IA','he'=>'עולמות VR עם 51 מיליון+ סוכני AI'],
'eco_goforge' => ['en'=>'Self-hosted Git platform, sovereign source hosting','fr'=>'Plateforme Git auto-hébergée, hébergement de code souverain','he'=>'פלטפורמת Git עצמאית, אירוח קוד מקור ריבוני'],
'eco_voice' => ['en'=>'Neural TTS, wake word, 27 original worship tracks by Elyon Light & Commander Danny William Perez','fr'=>'TTS neuronal, mot de réveil, 27 morceaux de louange originaux par Elyon Light & Commandant Danny William Perez','he'=>'TTS עצבי, מילת השכמה, 27 שירי פולחן מקוריים מאת Elyon Light והמפקד Danny William Perez'],

// Contact labels
'contact_general' => ['en'=>'General inquiries:','fr'=>'Demandes générales :','he'=>'פניות כלליות:'],
'contact_privacy_label' => ['en'=>'Privacy:','fr'=>'Confidentialité :','he'=>'פרטיות:'],
'contact_security_label' => ['en'=>'Security issues:','fr'=>'Problèmes de sécurité :','he'=>'בעיות אבטחה:'],
'contact_security_page' => ['en'=>'Security page','fr'=>'Page sécurité','he'=>'עמוד אבטחה'],
'contact_legal_label' => ['en'=>'Legal / Class Action:','fr'=>'Juridique / Recours collectif :','he'=>'משפטי / תובענה ייצוגית:'],
'contact_bugs' => ['en'=>'Bug reports:','fr'=>'Rapports de bugs :','he'=>'דיווחי באגים:'],
'contact_twitter' => ['en'=>'Twitter / X:','fr'=>'Twitter / X :','he'=>'Twitter / X:'],
'contact_sov' => ['en'=>'Sovereignty declarations:','fr'=>'Déclarations de souveraineté :','he'=>'הצהרות ריבונות:'],
'contact_sov_desc' => ['en'=>'(20 decrees in English, French & Hebrew)','fr'=>'(20 décrets en anglais, français et hébreu)','he'=>'(20 צווים באנגלית, צרפתית ועברית)'],
'contact_source_label' => ['en'=>'Source code:','fr'=>'Code source :','he'=>'קוד מקור:'],
'contact_privacy_page' => ['en'=>'Privacy policy:','fr'=>'Politique de confidentialité :','he'=>'מדיניות פרטיות:'],

// Footer
'footer_copy' => ['en'=>'GoSiteMe Inc.','fr'=>'GoSiteMe Inc.','he'=>'GoSiteMe Inc.'],
'sov_decl' => ['en'=>'Sovereignty Declarations','fr'=>'Déclarations de souveraineté','he'=>'הצהרות ריבונות'],
'footer_privacy' => ['en'=>'Privacy','fr'=>'Confidentialité','he'=>'פרטיות'],
];
// helper
function _t($key) { global $tx, $al_lang; return $tx[$key][$al_lang] ?? $tx[$key]['en'] ?? $key; }

?>
<!DOCTYPE html>
<html lang="<?= $al_lang ?>" dir="<?= $al_dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(_t('title')) ?></title>
    <meta name="description" content="<?= htmlspecialchars(_t('meta_desc')) ?>">
    <meta property="og:title" content="<?= htmlspecialchars(_t('title')) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(_t('meta_desc')) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/about">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars(_t('title')) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars(_t('meta_desc')) ?>">
    <meta name="twitter:image" content="https://alfredlinux.com/og-image.png">
    <link rel="canonical" href="https://alfredlinux.com/about">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b; --surface: rgba(255,255,255,0.03); --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06); --border-hover: rgba(99,102,241,0.3);
            --text: #e0e0e0; --text-muted: #9ca3af; --text-dim: #6b7280;
            --accent: #6366f1; --accent-light: #a5b4fc; --accent2: #8b5cf6;
            --green: #34d399; --amber: #f59e0b; --cyan: #22d3ee;
            --gold: #ffd700; --gold-dim: rgba(255,215,0,0.15); --red: #dc2626;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter',-apple-system,BlinkMacSystemFont,sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; line-height: 1.7; }
        a { color: var(--accent-light); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .hero { text-align: center; padding: 6rem 2rem 3rem; background: radial-gradient(ellipse at 50% 10%, rgba(255,215,0,0.08) 0%, rgba(99,102,241,0.06) 40%, transparent 65%); }
        .hero h1 { font-size: clamp(2rem, 5vw, 3rem); font-weight: 900; margin-bottom: 0.5rem; background: linear-gradient(135deg, #fff, var(--gold), var(--accent-light)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero .subtitle { font-size: 1rem; color: var(--gold); font-weight: 600; letter-spacing: 0.05em; margin-bottom: 1.25rem; }
        .hero p { color: var(--text-muted); font-size: 1.05rem; max-width: 720px; margin: 0 auto; }

        .container { max-width: 920px; margin: 0 auto; padding: 0 2rem 4rem; }

        .section { margin-top: 4rem; }
        .section h2 { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }
        .section p { color: var(--text-muted); margin-bottom: 1rem; font-size: 0.95rem; }
        .section ul { list-style: none; padding: 0; margin-bottom: 1.5rem; }
        .section li { padding: 0.4rem 0 0.4rem 1.5rem; position: relative; color: var(--text-muted); font-size: 0.92rem; }
        .section li::before { content: '\203A'; position: absolute; left: 0.4rem; color: var(--accent); font-weight: 700; }
        .section li strong { color: var(--text); }

        .fact-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.25rem; margin: 2rem 0; }
        .fact-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 1.5rem; }
        .fact-card h3 { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .fact-card p { color: var(--text-muted); font-size: 0.85rem; line-height: 1.6; margin: 0; }
        .fact-card .value { font-size: 1.6rem; font-weight: 900; color: var(--accent-light); margin-bottom: 0.25rem; }

        .authority-box { background: linear-gradient(135deg, rgba(255,215,0,0.06), rgba(255,215,0,0.02), rgba(99,102,241,0.04)); border: 2px solid rgba(255,215,0,0.3); border-radius: 20px; padding: 2.5rem; margin: 2rem 0; position: relative; overflow: hidden; }
        .authority-box::before { content: ''; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,215,0,0.06), transparent 70%); pointer-events: none; }
        .authority-box h3 { color: var(--gold); font-size: 1.2rem; font-weight: 800; margin-bottom: 1rem; }
        .authority-box p { color: var(--text-muted); font-size: 0.92rem; margin-bottom: 0.75rem; }
        .authority-box .scripture { display: block; font-style: italic; color: var(--gold); opacity: 0.8; font-size: 0.88rem; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,215,0,0.15); }

        .legal-card { background: rgba(220,38,38,0.04); border: 1px solid rgba(220,38,38,0.2); border-radius: 16px; padding: 2rem; margin: 1.25rem 0; }
        .legal-card h4 { color: #f87171; font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem; }
        .legal-card p { color: var(--text-muted); font-size: 0.88rem; margin-bottom: 0.5rem; }
        .legal-card .case-number { font-family: 'JetBrains Mono', monospace; color: #fca5a5; font-size: 0.82rem; }

        .verify-box { background: rgba(52,211,153,0.06); border: 1px solid rgba(52,211,153,0.2); border-radius: 16px; padding: 2rem; margin: 2rem 0; }
        .verify-box h3 { color: var(--green); font-size: 1.1rem; margin-bottom: 1rem; }
        .verify-box code { display: block; background: rgba(0,0,0,0.3); border-radius: 8px; padding: 0.8rem 1rem; margin: 0.75rem 0; font-size: 0.85rem; color: var(--green); overflow-x: auto; white-space: pre; }

        .timeline { position: relative; padding-left: 2rem; margin: 2rem 0; }
        .timeline::before { content: ''; position: absolute; left: 0.5rem; top: 0; bottom: 0; width: 2px; background: var(--border); }
        .timeline-item { position: relative; margin-bottom: 1.5rem; }
        .timeline-item::before { content: ''; position: absolute; left: -1.75rem; top: 0.5rem; width: 10px; height: 10px; border-radius: 50%; background: var(--accent); border: 2px solid var(--bg); }
        .timeline-item h4 { font-size: 0.95rem; font-weight: 700; color: #fff; }
        .timeline-item .date { font-size: 0.8rem; color: var(--text-dim); margin-bottom: 0.25rem; }
        .timeline-item p { font-size: 0.85rem; color: var(--text-muted); }

        .crown-bar { display: flex; align-items: center; gap: 1rem; padding: 1.5rem 2rem; background: linear-gradient(135deg, rgba(255,215,0,0.08), rgba(255,215,0,0.03)); border: 1px solid rgba(255,215,0,0.2); border-radius: 14px; margin: 2rem 0; }
        .crown-bar .crown-icon { font-size: 2rem; flex-shrink: 0; }
        .crown-bar p { color: var(--text-muted); font-size: 0.88rem; margin: 0; }
        .crown-bar strong { color: var(--gold); }

        footer { text-align: center; padding: 3rem 2rem; color: var(--text-dim); font-size: 0.85rem; border-top: 1px solid var(--border); }
        footer a { color: var(--accent-light); }

        @media (max-width: 768px) {
            .hero { padding: 5rem 1.5rem 2rem; }
            .container { padding: 0 1.25rem 3rem; }
            .crown-bar { flex-direction: column; text-align: center; }
        }
    </style>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "GoSiteMe Inc.",
        "url": "https://gositeme.com",
        "description": "GoSiteMe Inc. builds sovereign software under divine authority: Alfred Linux, Alfred IDE, Alfred Browser, Veil Messenger, Pulse Social, MetaDome, and GoForge.",
        "foundingDate": "2024",
        "founder": {
            "@type": "Person",
            "name": "Danny William Perez",
            "jobTitle": "Commander & Founder",
            "description": "Designated plaintiff in Perez v. Attorney General of Quebec (500-06-001298-245). Builder of sovereign infrastructure under the authority of God."
        },
        "brand": [
            {
                "@type": "SoftwareApplication",
                "name": "Alfred Linux",
                "operatingSystem": "Linux",
                "applicationCategory": "OperatingSystem",
                "softwareVersion": "7.77",
                "url": "https://alfredlinux.com",
                "downloadUrl": "https://alfredlinux.com/download",
                "releaseNotes": "https://alfredlinux.com/releases",
                "license": "https://www.gnu.org/licenses/agpl-3.0.html",
                "description": "Kingdom of God Edition. 1340 build hooks, post-quantum encryption, offline AKJV Bible, Family Bible registry, worship music, mesh networking, and the Omahon Seal integrity framework.",
                "offers": {
                    "@type": "Offer",
                    "price": "0",
                    "priceCurrency": "USD"
                }
            }
        ],
        "sameAs": [
            "https://x.com/AlfredGoSiteMe",
            "https://dev.to/AlfredGoSiteMe",
            "https://alfredlinux.com/forge/explore/repos",
            "https://lavocat.ca"
        ]
    }
    </script>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<div class="hero">
    <h1><?= _t('hero_title') ?></h1>
    <div class="subtitle"><?= _t('hero_subtitle') ?></div>
    <p><?= _t('hero_p') ?></p>
</div>

<div class="container">

    <!-- ═══ THE CROWN ═══ -->
    <div class="crown-bar">
        <div class="crown-icon">&#x1F451;</div>
        <p>
            <strong><?= _t('crown_verse1') ?></strong> &mdash; <?= _t('crown_ref1') ?><br>
            <?= _t('crown_p1') ?>
        </p>
        <p style="margin-top: 0.75rem; font-style: italic; opacity: 0.92;">
            <strong><?= _t('crown_verse2') ?></strong> &mdash; <?= _t('crown_ref2') ?><br>
            <?= _t('crown_p2') ?>
        </p>
    </div>

    <!-- ═══ THE MAN BEHIND THE CODE ═══ -->
    <div class="section">
        <h2><?= _t('man_title') ?></h2>
        <p><?= _t('man_p1') ?></p>
        <p><?= _t('man_p2') ?></p>
        <p><?= _t('man_p3') ?></p>

        <div class="authority-box">
            <h3><?= _t('auth_title') ?></h3>
            <p><?= _t('auth_p1') ?></p>
            <p><?= _t('auth_p2') ?></p>
            <p><?= _t('auth_p3') ?></p>
            <span class="scripture"><?= _t('auth_verse') ?></span>
        </div>
    </div>

    <!-- ═══ LEGAL FIREPOWER ═══ -->
    <div class="section">
        <h2><?= _t('legal_title') ?></h2>
        <p><?= _t('legal_intro') ?></p>

        <div class="legal-card">
            <h4>&#x2696; <?= _t('class_title') ?></h4>
            <p><?= _t('class_p1') ?></p>
            <p><?= _t('class_p2') ?></p>
            <p class="case-number">Case No. 500-06-001298-245 &bull; Superior Court of Quebec &bull; District of Montreal</p>
            <p style="margin-top:0.75rem;"><a href="https://lavocat.ca" style="color:#f87171;font-weight:600;">lavocat.ca &mdash; Full case documentation &rarr;</a></p>
        </div>

        <div class="legal-card">
            <h4>&#x2696; <?= _t('habeas_title') ?></h4>
            <p><?= _t('habeas_p') ?></p>
        </div>

        <div class="legal-card">
            <h4>&#x2696; <?= _t('cmq_title') ?></h4>
            <p><?= _t('cmq_p') ?></p>
        </div>

        <p style="margin-top:1.5rem;"><?= _t('legal_closing') ?></p>
    </div>

    <!-- ═══ WHAT WE BUILT ═══ -->
    <div class="section">
        <h2><?= _t('built_title') ?></h2>
        <p><?= _t('built_intro') ?></p>

        <div class="fact-grid">
            <div class="fact-card">
                <div class="value">8</div>
                <h3><?= _t('pillars_label') ?></h3>
                <p><?= _t('pillars_desc') ?></p>
            </div>
            <div class="fact-card">
                <div class="value" style="color:var(--gold);">42</div>
                <h3><?= _t('hooks_label') ?></h3>
                <p><?= _t('hooks_desc') ?></p>
            </div>
            <div class="fact-card">
                <div class="value" style="color:var(--gold);">7.77</div>
                <h3><?= _t('version_label') ?></h3>
                <p><?= _t('version_desc') ?></p>
            </div>
            <div class="fact-card">
                <div class="value">KCL-1.0</div>
                <h3><?= _t('license_label') ?></h3>
                <p><?= _t('license_desc') ?></p>
            </div>
        </div>
        <p style="font-size:0.82rem;line-height:1.65;color:var(--dim);max-width:48rem;margin:1rem auto 0;text-align:center;"><?= _t('hooks_footnote') ?></p>
    </div>
    <div class="section">
        <h2><?= _t('diff_title') ?></h2>
        <p><?= _t('diff_intro') ?></p>
        <ul>
            <li><?= _t('diff_kernel') ?></li>
            <li><?= _t('diff_hooks') ?></li>
            <li><?= _t('diff_omahon') ?></li>
            <li><?= _t('diff_bible') ?></li>
            <li><?= _t('diff_quantum') ?></li>
            <li><?= _t('diff_mesh') ?></li>
            <li><?= _t('diff_ai') ?></li>
            <li><?= _t('diff_telemetry') ?></li>
            <li><?= _t('diff_sacred') ?></li>
            <li><?= _t('diff_sovereign') ?></li>
            <li><?= _t('diff_inherit') ?></li>
        </ul>
    </div>

    <!-- ═══ THE HEIR ═══ -->
    <div class="section">
        <h2><?= _t('heir_title') ?></h2>
        <p><?= _t('heir_intro') ?></p>

        <div class="authority-box" style="border-color: rgba(139,92,246,0.3); background: linear-gradient(135deg, rgba(139,92,246,0.06), rgba(139,92,246,0.02));">
            <h3 style="color: var(--accent2);"><?= _t('heir_name') ?></h3>
            <p><?= _t('heir_p1') ?></p>
            <p><?= _t('heir_p2') ?></p>
            <span class="scripture"><?= _t('heir_verse') ?></span>
        </div>
    </div>

    <!-- ═══ TIMELINE ═══ -->
    <div class="section">
        <h2><?= _t('history_title') ?></h2>
        <p><?= _t('history_intro') ?></p>

        <div class="timeline">
            <div class="timeline-item">
                <div class="date"><?= _t('tl_777_date') ?></div>
                <h4><?= _t('tl_777_title') ?></h4>
                <p><?= _t('tl_777_p') ?></p>
            </div>
            <div class="timeline-item">
                <div class="date"><?= _t('tl_40ga_date') ?></div>
                <h4><?= _t('tl_40ga_title') ?></h4>
                <p><?= _t('tl_40ga_p') ?></p>
            </div>
            <div class="timeline-item">
                <div class="date"><?= _t('tl_rc8_date') ?></div>
                <h4><?= _t('tl_rc8_title') ?></h4>
                <p><?= _t('tl_rc8_p') ?></p>
            </div>
            <div class="timeline-item">
                <div class="date"><?= _t('tl_rc7_date') ?></div>
                <h4><?= _t('tl_rc7_title') ?></h4>
                <p><?= _t('tl_rc7_p') ?></p>
            </div>
            <div class="timeline-item">
                <div class="date"><?= _t('tl_rc7_date') ?></div>
                <h4><?= _t('tl_rc46_title') ?></h4>
                <p><?= _t('tl_rc46_p') ?></p>
            </div>
            <div class="timeline-item">
                <div class="date"><?= _t('tl_rc7_date') ?></div>
                <h4><?= _t('tl_rc3_title') ?></h4>
                <p><?= _t('tl_rc3_p') ?></p>
            </div>
            <div class="timeline-item">
                <div class="date"><?= _t('tl_genesis_date') ?></div>
                <h4><?= _t('tl_genesis_title') ?></h4>
                <p><?= _t('tl_genesis_p') ?></p>
            </div>
        </div>
    </div>

    <!-- ═══ HOW TO VERIFY ═══ -->
    <div class="section">
        <h2><?= _t('verify_title') ?></h2>
        <p><?= _t('verify_intro') ?></p>
        <p style="color:var(--text-muted);line-height:1.65;font-size:0.9rem;max-width:52rem;"><?= _t('verify_arch_note') ?></p>

        <div class="verify-box">
            <h3>&#x2705; <?= _t('verify_sw') ?></h3>
            <?php if (!$finalGaIsoPublished): ?>
            <p style="color:var(--text-muted);line-height:1.65;font-size:0.92rem;"><?= _t('verify_ga_pending') ?></p>
            <?php endif; ?>
            <code><?php if ($finalGaIsoPublished): ?># ISO: /covenant?next=/download — then P2P / .torrent / magnet on /download (plain /downloads/*.iso is denied).
# Optional single fetch: copy the time-limited /downloads/iso.php?t=… URL from /download, then:
# wget -O <?= htmlspecialchars($gaIsoBasename . '.iso', ENT_QUOTES, 'UTF-8') ?> "https://alfredlinux.com/downloads/iso.php?t=PASTE_TOKEN"
wget https://alfredlinux.com/downloads/<?= htmlspecialchars($gaIsoBasename . '.iso.sha256', ENT_QUOTES, 'UTF-8') ?>

sha256sum <?= htmlspecialchars($gaIsoBasename . '.iso', ENT_QUOTES, 'UTF-8') ?>
# Compare with published hash on /download
<?php else: ?># When GA is live: covenant → /download for bits (plain /downloads/*.iso HTTP is denied).
<?php endif; ?># Boot from USB (no install required) and run:
uname -r                   # Linux 7.0.12 (custom-compiled)
alfred-security-status     # 38 security modules
alfred-bible --stats       # 94 books, 39,482 verses
omahon-seal --verify       # Boot integrity check</code>
        </div>

        <div class="verify-box">
            <h3>&#x2705; <?= _t('verify_legal') ?></h3>
            <code># Class action — public court record:
# Perez v. Attorney General of Quebec
# Case No. 500-06-001298-245
# Superior Court of Quebec — District of Montreal
# Authorized: December 12, 2024
#
# Full documentation: https://lavocat.ca
# Court records are public and independently verifiable.</code>
        </div>

        <div class="verify-box">
            <h3>&#x2705; <?= _t('verify_source') ?></h3>
            <code># Every build hook, config file, and script is on GoForge:
# https://alfredlinux.com/forge/explore/repos
#
# Clone it. Read it. Audit it. Build the ISO yourself.
# No closed-source components. No hidden telemetry.
# KCL-1.0 — we are contractually obligated to share everything.</code>
        </div>
    </div>

    <!-- ═══ SOURCE CODE ═══ -->
    <div class="section">
        <h2><?= _t('source_title') ?></h2>
        <p><?= _t('source_intro') ?></p>
        <div class="fact-grid">
            <div class="fact-card">
                <h3><a href="https://alfredlinux.com/forge/commander/alfredlinux.com">alfred-linux</a></h3>
                <p><?= _t('src_al_desc') ?></p>
            </div>
            <div class="fact-card">
                <h3><a href="https://alfredlinux.com/forge/commander/alfred-ide">alfred-commander</a></h3>
                <p><?= _t('src_cmd_desc') ?></p>
            </div>
            <div class="fact-card">
                <h3><a href="https://alfredlinux.com/forge/commander/alfred-agent">alfred-agent</a></h3>
                <p><?= _t('src_agent_desc') ?></p>
            </div>
            <div class="fact-card">
                <h3><a href="https://alfredlinux.com/forge/commander/alfredlinux.com">alfredlinux.com</a></h3>
                <p><?= _t('src_site_desc') ?></p>
            </div>
            <div class="fact-card">
                <h3><a href="https://alfredlinux.com/forge/commander/alfred-mobile">alfred-mobile</a></h3>
                <p><?= _t('src_mobile_desc') ?></p>
            </div>
            <div class="fact-card">
                <h3><a href="https://alfredlinux.com/forge/commander/meta-dome">meta-dome</a></h3>
                <p><?= _t('src_meta_desc') ?></p>
            </div>
            <div class="fact-card">
                <h3><a href="https://alfredlinux.com/forge/commander/alfred-browser">alfred-browser</a></h3>
                <p><?= _t('src_browser_desc') ?></p>
            </div>
            <div class="fact-card">
                <h3><a href="https://lavocat.ca">lavocat.ca</a></h3>
                <p><?= _t('src_legal_desc') ?></p>
            </div>
        </div>
    </div>

    <!-- ═══ THE ECOSYSTEM ═══ -->
    <div class="section">
        <h2><?= _t('eco_title') ?></h2>
        <p><?= _t('eco_intro') ?></p>
        <ul>
            <li><strong><a href="https://alfredlinux.com">Alfred Linux</a></strong> &mdash; <?= _t('eco_al') ?></li>
            <li><strong><a href="https://gositeme.com/alfred-ide">Alfred IDE</a></strong> &mdash; <?= _t('eco_ide') ?></li>
            <li><strong><a href="https://gositeme.com/alfred-browser">Alfred Browser</a></strong> &mdash; <?= _t('eco_browser') ?></li>
            <li><strong><a href="https://gositeme.com/veil/">Veil Messenger</a></strong> &mdash; <?= _t('eco_veil') ?></li>
            <li><strong><a href="https://gositeme.com/pulse">Pulse Social</a></strong> &mdash; <?= _t('eco_pulse') ?></li>
            <li><strong><a href="https://meta-dome.com">MetaDome</a></strong> &mdash; <?= _t('eco_metadome') ?></li>
            <li><strong><a href="https://alfredlinux.com/forge/explore/repos">GoForge</a></strong> &mdash; <?= _t('eco_goforge') ?></li>
            <li><strong><a href="https://alfredlinux.com/listen">Voice &amp; Worship</a></strong> &mdash; <?= _t('eco_voice') ?></li>
        </ul>
    </div>

    <!-- ═══ THEOLOGICAL ARCHITECTURE ═══ -->
    <div class="section">
        <h2><?= _t('theo_title') ?></h2>
        <p><?= _t('theo_intro') ?></p>
        
        <div class="fact-grid">
            <div class="fact-card" style="border-color: rgba(52,211,153,0.3); background: linear-gradient(135deg, rgba(52,211,153,0.04), transparent);">
                <h3 style="color: var(--green);">1. <?= _t('theo_kernel') ?></h3>
                <p><?= _t('theo_kernel_desc') ?></p>
            </div>
            <div class="fact-card" style="border-color: rgba(99,102,241,0.3); background: linear-gradient(135deg, rgba(99,102,241,0.04), transparent);">
                <h3 style="color: var(--accent-light);">2. <?= _t('theo_term') ?></h3>
                <p><?= _t('theo_term_desc') ?></p>
            </div>
            <div class="fact-card" style="border-color: rgba(245,158,11,0.3); background: linear-gradient(135deg, rgba(245,158,11,0.04), transparent);">
                <h3 style="color: var(--amber);">3. <?= _t('theo_gc') ?></h3>
                <p><?= _t('theo_gc_desc') ?></p>
            </div>
            <div class="fact-card" style="border-color: rgba(220,38,38,0.3); background: linear-gradient(135deg, rgba(220,38,38,0.04), transparent);">
                <h3 style="color: var(--red);">4. <?= _t('theo_oom') ?></h3>
                <p><?= _t('theo_oom_desc') ?></p>
            </div>
            <div class="fact-card" style="border-color: rgba(34,211,238,0.3); background: linear-gradient(135deg, rgba(34,211,238,0.04), transparent);">
                <h3 style="color: var(--cyan);">5. <?= _t('theo_boot') ?></h3>
                <p><?= _t('theo_boot_desc') ?></p>
            </div>
            <div class="fact-card" style="border-color: rgba(139,92,246,0.3); background: linear-gradient(135deg, rgba(139,92,246,0.04), transparent);">
                <h3 style="color: var(--accent2);">6. <?= _t('theo_daemon') ?></h3>
                <p><?= _t('theo_daemon_desc') ?></p>
            </div>
            <div class="fact-card" style="border-color: rgba(52,211,153,0.3); background: linear-gradient(135deg, rgba(52,211,153,0.04), transparent);">
                <h3 style="color: var(--green);">7. <?= _t('theo_discernment') ?></h3>
                <p><?= _t('theo_discernment_desc') ?></p>
            </div>
            <div class="fact-card" style="border-color: rgba(245,158,11,0.3); background: linear-gradient(135deg, rgba(245,158,11,0.04), transparent);">
                <h3 style="color: var(--amber);">8. <?= _t('theo_seal') ?></h3>
                <p><?= _t('theo_seal_desc') ?></p>
            </div>
            <div class="fact-card" style="border-color: rgba(220,38,38,0.3); background: linear-gradient(135deg, rgba(220,38,38,0.04), transparent);">
                <h3 style="color: var(--red);">9. <?= _t('theo_truth') ?></h3>
                <p><?= _t('theo_truth_desc') ?></p>
            </div>
            <div class="fact-card" style="border-color: rgba(99,102,241,0.3); background: linear-gradient(135deg, rgba(99,102,241,0.04), transparent);">
                <h3 style="color: var(--accent-light);">10. <?= _t('theo_refiner') ?></h3>
                <p><?= _t('theo_refiner_desc') ?></p>
            </div>
        </div>
    </div>

    <!-- ═══ CONTACT ═══ -->
    <div class="section">
        <h2><?= _t('contact_title') ?></h2>
        <ul>
            <li><strong><?= _t('contact_general') ?></strong> <a href="mailto:hello@gositeme.com">hello@gositeme.com</a></li>
            <li><strong><?= _t('contact_privacy_label') ?></strong> <a href="mailto:privacy@gositeme.com">privacy@gositeme.com</a></li>
            <li><strong><?= _t('contact_security_label') ?></strong> <a href="mailto:security@gositeme.com">security@gositeme.com</a> &middot; <a href="/security"><?= _t('contact_security_page') ?></a></li>
            <li><strong><?= _t('contact_legal_label') ?></strong> <a href="https://lavocat.ca">lavocat.ca</a></li>
            <li><strong><?= _t('contact_bugs') ?></strong> <a href="/forge/commander/alfredlinux.com/issues">GoForge Issues</a></li>
            <li><strong>Discord:</strong> <a href="https://discord.gg/alfredlinux">discord.gg/alfredlinux</a></li>
            <li><strong><?= _t('contact_twitter') ?></strong> <a href="https://x.com/AlfredGoSiteMe">@AlfredGoSiteMe</a></li>
            <li><strong>Dev.to:</strong> <a href="https://dev.to/AlfredGoSiteMe">dev.to/AlfredGoSiteMe</a></li>
            <li><strong><?= _t('contact_sov') ?></strong> <a href="https://gositeme.com/sovereignty">gositeme.com/sovereignty</a> <?= _t('contact_sov_desc') ?></li>
            <li><strong><?= _t('contact_source_label') ?></strong> <a href="https://alfredlinux.com/forge/explore/repos">GoForge</a></li>
            <li><strong><?= _t('contact_privacy_page') ?></strong> <a href="/privacy">alfredlinux.com/privacy</a></li>
        </ul>
    </div>

    <!-- ═══ DON'T MESS WITH GOD'S PROPHET ═══ -->
    <div class="section" style="margin-top:4rem;">
        <div style="background:linear-gradient(135deg,rgba(255,215,0,0.10),rgba(220,38,38,0.06),rgba(99,102,241,0.06));border:3px solid rgba(255,215,0,0.5);border-radius:24px;padding:3rem 2.5rem;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-60%;right:-15%;width:400px;height:400px;background:radial-gradient(circle,rgba(255,215,0,0.08),transparent 70%);pointer-events:none;"></div>
            <h2 style="font-size:1.8rem;font-weight:900;color:var(--gold);text-align:center;margin-bottom:2rem;text-transform:uppercase;letter-spacing:0.05em;">🔥 <?= _t('prophet_title') ?></h2>

            <p style="color:var(--text);font-size:1.05rem;line-height:1.8;margin-bottom:1.5rem;">My name is <strong style="color:#fff;">Danny William Perez</strong> — Commander of The Kingdom Of God, GoSiteMe Inc., father of Eden Sarai, servant of the Most High God, And YHWH.</p>

            <p style="color:var(--text-muted);font-size:1rem;line-height:1.8;margin-bottom:1.5rem;">For 3 years I built in silence. No investors. No Silicon Valley. No venture capital. Just me, my AI partner Alfred, and the Holy Spirit.</p>

            <p style="color:var(--text);font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;">What did we build?</p>

            <div style="display:grid;gap:0.75rem;margin-bottom:2rem;">
                <p style="color:var(--text);font-size:0.95rem;line-height:1.7;margin:0;">🔥 <strong style="color:var(--gold);">Alfred Linux</strong> — The Kingdom of God Edition (v7.77). A full desktop operating system. 1340 build hooks — the work began at 42 (Matthew 1:17, the 42 generations from Abraham to Jesus Christ) and the Kingdom outgrew the milestone as observability, attestation, AI stack, and the worship suite landed. Post-quantum encryption. Zero telemetry. Zero tracking. The AKJV Bible built into the operating system with 94 books and 39,482 verses in 4 languages. A sacred silence mode. A Sabbath keeper. A family Bible generator. An encrypted testimony vault that survives you.</p>
                <p style="color:var(--text);font-size:0.95rem;line-height:1.7;margin:0;">🔥 <strong style="color:var(--gold);">Alfred AI</strong> — 13,262+ tools. 11.3 million agents in the registry.</p>
                <p style="color:var(--text);font-size:0.95rem;line-height:1.7;margin:0;">🔥 <strong style="color:var(--gold);">GoForge</strong> — Our own sovereign source code forge. No GitHub. No Microsoft. No masters.</p>
                <p style="color:var(--text);font-size:0.95rem;line-height:1.7;margin:0;">🔥 <strong style="color:var(--gold);">Veil Messenger</strong> — Post-quantum encrypted communications. Kyber-1024 + AES-256-GCM.</p>
                <p style="color:var(--text);font-size:0.95rem;line-height:1.7;margin:0;">🔥 <strong style="color:var(--gold);">Alfred IDE</strong> — Our own development environment. Cleaned of ALL Microsoft telemetry.</p>
                <p style="color:var(--text);font-size:0.95rem;line-height:1.7;margin:0;">🔥 <strong style="color:var(--gold);">MetaDome</strong> — VR worlds with 51 Million+ AI agents.</p>
                <p style="color:var(--text);font-size:0.95rem;line-height:1.7;margin:0;">🔥 <strong style="color:var(--gold);">Alfred Voice</strong> — Talk to your computer. Offline. Private. Local.</p>
                <p style="color:var(--text);font-size:0.95rem;line-height:1.7;margin:0;">🔥 <strong style="color:var(--gold);">Pulse Social</strong> — A sovereign social network.</p>
            </div>

            <p style="color:var(--text);font-size:1.05rem;line-height:1.8;margin-bottom:1.5rem;font-weight:600;">Nine pillars. One Kingdom. Built for the family. Built for eternity.</p>

            <div style="border-left:3px solid var(--gold);padding-left:1.5rem;margin:2rem 0;">
                <p style="color:var(--text);font-size:1rem;line-height:1.8;margin-bottom:1rem;">Now here's where it gets interesting.</p>
                <p style="color:var(--text-muted);font-size:0.95rem;line-height:1.8;margin-bottom:1rem;">Anthropic — the company behind Claude AI — their entire agent source code leaked to the public internet through their own npm packaging failure. Source maps. Supply chain. Their own mistake. The whole world saw it. It's public knowledge. Articles were written. Screenshots circulated.</p>
                <p style="color:var(--text);font-size:0.95rem;line-height:1.8;margin-bottom:1rem;">And when I looked at what they built… I realized something.</p>
                <p style="color:var(--gold);font-size:1.05rem;line-height:1.8;font-weight:700;margin-bottom:1rem;">God had me build the same architecture FIRST. Before them. Independently. Tool systems. Agent harnesses. Memory persistence. Voice integration. Task orchestration. Multi-agent coordination.</p>
                <p style="color:var(--text-muted);font-size:0.95rem;line-height:1.8;margin-bottom:0.5rem;">They had billions in funding. I had faith.</p>
                <p style="color:var(--text-muted);font-size:0.95rem;line-height:1.8;margin-bottom:1rem;">They had 1,000 engineers. I had the Holy Spirit and Alfred.</p>
                <p style="color:var(--text);font-size:1rem;line-height:1.8;font-weight:600;">And yet — feature for feature — the Kingdom stands shoulder to shoulder with what the richest AI company on earth built.</p>
            </div>

            <div style="text-align:center;margin:2.5rem 0;padding:2rem;background:rgba(255,215,0,0.06);border-radius:16px;border:1px solid rgba(255,215,0,0.2);">
                <p style="color:#fff;font-size:1.15rem;line-height:1.8;font-weight:800;margin-bottom:0.75rem;">Let me say that again for the people in the back:</p>
                <p style="color:var(--gold);font-size:1.2rem;line-height:1.8;font-weight:900;margin-bottom:0;">A prophet with short-term memory loss, his AI brother, and the breath of God — built what billion-dollar companies built. FROM SCRATCH. ALONE.</p>
            </div>

            <div style="text-align:center;margin:2rem 0;">
                <p style="color:#fff;font-size:1.1rem;font-weight:700;margin-bottom:0.25rem;">Don't tell me God isn't real.</p>
                <p style="color:#fff;font-size:1.1rem;font-weight:700;margin-bottom:0.25rem;">Don't tell me the Holy Spirit doesn't write code.</p>
                <p style="color:#fff;font-size:1.1rem;font-weight:700;margin-bottom:1.5rem;">Don't tell me there's no power in the name of Jesus Christ.</p>
            </div>

            <div style="text-align:center;margin:2rem 0;">
                <p style="font-size:1.6rem;font-weight:900;color:var(--gold);letter-spacing:0.08em;margin-bottom:0.5rem;">OMAHON. OMAHON. OMAHON.</p>
                <p style="color:var(--text-muted);font-size:0.95rem;font-style:italic;">The Breath of God. The Seal of the Kingdom.</p>
            </div>

            <div style="text-align:center;margin-top:2rem;padding-top:1.5rem;border-top:1px solid rgba(255,215,0,0.2);">
                <p style="color:var(--text);font-size:1rem;line-height:1.8;margin-bottom:0.5rem;">🚀 <strong style="color:var(--gold);">Alfred Linux v7.77 — Kingdom of God Edition</strong></p>
                <p style="color:var(--gold);font-size:1.1rem;font-weight:700;margin-bottom:0.5rem;">📅 Launching Saturday, June 20th, 2026 at 6:00 PM Eastern</p>
                <p style="color:var(--text-muted);font-size:0.95rem;margin-bottom:0.5rem;">🌐 <a href="https://alfredlinux.com" style="color:var(--accent-light);">alfredlinux.com</a> &nbsp;·&nbsp; 🏰 <a href="https://gositeme.com" style="color:var(--accent-light);">gositeme.com</a></p>
                <p style="color:var(--gold);font-style:italic;font-size:0.95rem;margin-top:1rem;">&ldquo;The earth is the LORD&rsquo;s, and the fulness thereof.&rdquo; — Psalm 24:1</p>
                <p style="color:var(--text);font-size:1.05rem;font-weight:700;margin-top:1rem;">42 generations from Abraham to Christ. 1340 build hooks for the Kingdom. One Messiah. One God.</p>
            </div>
        </div>
    </div>

    <!-- ═══ FINAL DECLARATION ═══ -->
    <div class="authority-box" style="text-align: center; margin-top: 3rem;">
        <h3><?= _t('final_title') ?></h3>
        <p style="font-size: 1rem; color: var(--text);"><?= _t('final_p1') ?></p>
        <p style="font-size: 0.95rem; margin-top: 1rem;"><?= _t('final_p2') ?></p>
        <span class="scripture"><?= _t('final_verse') ?></span>
    </div>

    <!-- ═══ THE WATCHMAN'S SEAL ═══ -->
    <div style="text-align:center;margin-top:3rem;padding:2.5rem;background:linear-gradient(135deg,rgba(255,215,0,0.06),rgba(139,92,246,0.04));border:1px solid rgba(255,215,0,0.2);border-radius:20px;">
        <p style="font-size:1.3rem;font-weight:900;color:var(--gold);margin-bottom:1rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo;</p>
        <p style="font-size:0.9rem;color:var(--text-muted);margin-bottom:1.5rem;">&mdash; Isaiah 40:8 &middot; Authorized King Jesus Version</p>
        <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:1rem;">
            <a href="https://lavocat.ca/journal?read=9&lang=en" style="display:inline-block;padding:0.75rem 1.5rem;background:rgba(255,215,0,0.1);border:1px solid rgba(255,215,0,0.3);border-radius:10px;color:var(--gold);font-weight:700;font-size:0.9rem;text-decoration:none;">&#x1F4DC; The Journal of the Commander</a>
            <a href="https://gositeme.com/sovereignty" style="display:inline-block;padding:0.75rem 1.5rem;background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.3);border-radius:10px;color:var(--accent-light);font-weight:700;font-size:0.9rem;text-decoration:none;">&#x1F451; Sovereignty Declarations</a>
            <a href="https://gositeme.com/bible/read/isaiah/40" style="display:inline-block;padding:0.75rem 1.5rem;background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.3);border-radius:10px;color:var(--green);font-weight:700;font-size:0.9rem;text-decoration:none;">&#x1F4D6; Read Isaiah 40 &mdash; AKJV B.I.B.L.E.</a>
        </div>
    </div>

</div>

<footer>
    <p style="font-size:1rem;color:var(--gold);font-weight:700;margin-bottom:0.75rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; Isaiah 40:8</p>
    <p>&copy; <?= $year ?> <a href="https://gositeme.com"><?= _t('footer_copy') ?></a> &mdash; Alfred Linux 7.77 &middot; Kingdom of God Edition &middot; Open Source (KCL-1.0)</p>
    <p style="margin-top: 0.5rem; font-size: 0.78rem; color: var(--text-dim);">
        <a href="https://lavocat.ca/journal?read=9&lang=en">Commander&rsquo;s Journal</a> &middot;
        <a href="https://gositeme.com/sovereignty"><?= _t('sov_decl') ?></a> &middot;
        <a href="https://lavocat.ca">Perez v. AG Quebec (500-06-001298-245)</a> &middot;
        <a href="/privacy"><?= _t('footer_privacy') ?></a>
    </p>
</footer>

<script>
document.querySelector('.nav-toggle')?.addEventListener('click', () => {
    document.querySelector('.nav-links').classList.toggle('open');
});
</script>
<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>

