<?php
session_start();
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json');

$message = isset($_POST['message']) ? trim(mb_strtolower($message)) : '';
$response = "";

if (empty($message)) {
    echo json_encode(['response' => "🐔 Bonjour ! Je suis votre assistant avicole. Posez-moi une question sur l'élevage de poulets (maladies, alimentation, comportement, vaccination...)"]);
    exit();
}

// ==================== BASE DE CONNAISSANCE ULTRA COMPLÈTE ====================
$knowledge = [];

// ----- MALADIES -----
$knowledge['coccidiose'] = "🐔 **COCCIDIOSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🔬 **Cause** : Parasite (Eimeria)\n🩺 **Symptômes** : Diarrhée sanglante ou aqueuse, plumes hérissées, abattement, ailes tombantes, pâleur de la crête\n💊 **Traitement** : Amprolium (0.025%) dans l'eau - 5 jours\n🛡️ **Prévention** : Litière toujours sèche, nettoyage régulier, anticoccidiens dans l'aliment\n⚠️ **Urgence** : Mortalité rapide chez les jeunes";
$knowledge['symptomes coccidiose'] = $knowledge['coccidiose'];
$knowledge['comment traiter la coccidiose'] = $knowledge['coccidiose'];
$knowledge['diarrhee sanglante'] = $knowledge['coccidiose'];

$knowledge['newcastle'] = "⚠️ **MALADIE DE NEWCASTLE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🦠 **Cause** : Virus paramyxovirus\n🩺 **Symptômes** : Respiration bruyante, toux, paralysie des pattes/ailes, cou tordu, diarrhée verte\n💊 **Action** : URGENCE VÉTÉRINAIRE IMMÉDIATE ! Isolement strict\n💉 **Prévention** : Vaccination obligatoire (J14, J42)\n📉 **Mortalité** : Peut atteindre 100% dans un lot non vacciné";
$knowledge['maladie de newcastle'] = $knowledge['newcastle'];
$knowledge['newcatsle symptomes'] = $knowledge['newcastle'];

$knowledge['salmonellose'] = "🦠 **SALMONELLOSE (PULLOROSE)**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🩺 **Symptômes** : Diarrhée blanche mousseuse, abattement sévère, plumes hérissées, perte d'appétit\n💊 **Traitement** : Antibiotiques (florfenicol, enrofloxacine) sur prescription\n🛡️ **Prévention** : Désinfection des locaux, contrôle des rongeurs, éviter vols sauvages\n⚠️ **Risque** : Zoonose transmissible à l'homme !";
$knowledge['diarrhee blanche'] = $knowledge['salmonellose'];

$knowledge['bronchite infectieuse'] = "🌬️ **BRONCHITE INFECTIEUSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🩺 **Symptômes** : Toux, éternuements, respiration bruyante, fièvre, chute de ponte (œufs déformés)\n💊 **Traitement** : Antibiotiques (tylosine) dans l'eau - 5 jours Aérez sans courant d'air\n💉 **Prévention** : Vaccination (J21)\n📉 **Conséquences** : Baisse de ponte jusqu'à 50%";
$knowledge['symptomes bronchite'] = $knowledge['bronchite infectieuse'];
$knowledge['infection respiratoire poulet'] = $knowledge['bronchite infectieuse'];

$knowledge['gumboro'] = "🛡️ **MALADIE DE GUMBORO**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🩺 **Symptômes** : Apathie sévère, léthargie, plumes hérissées, diarrhée blanche aqueuse, pics de mortalité\n💊 **Traitement** : Pas de traitement antiviral. Soutien immunitaire (vitamines E+C)\n💉 **Prévention** : Vaccination obligatoire (J7, J21)\n⚠️ **Particularité** : Très contagieuse, immunodépression sévère";
$knowledge['gumboro symptomes'] = $knowledge['gumboro'];

$knowledge['colibacillose'] = "🦠 **COLIBACILLOSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🩺 **Symptômes** : Difficultés respiratoires, péricardite (cœur enveloppé), abattement, mortalité\n💊 **Traitement** : Antibiotiques (colistine, amoxicilline) sur prescription\n🛡️ **Prévention** : Hygiène parfaite, éviter les courants d'air\n⚠️ **Risque** : Souvent secondaire à d'autres maladies";

$knowledge['mycoplasmose'] = "🦠 **MYCOPLASMOSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🩺 **Symptômes** : Éternuements chroniques, gonflement des sinus, écoulement nasal, respiration sifflante, faible mortalité\n💊 **Traitement** : Tylosine ou tiamuline dans l'eau (5-7 jours)\n🛡️ **Prévention** : Élevage hors-sol, ventilation, réduction du stress\n⚠️ **Transmission** : Verticale (œuf) et horizontale";

$knowledge['parasites internes'] = "🪱 **PARASITES INTERNES (VERS)**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🩺 **Symptômes** : Amaigrissement malgré bon appétit, diarrhée, faiblesse, baisse de ponte, pâleur\n💊 **Traitement** : Fenbendazole (Panacur) ou levamisole dans l'alimentation\n🛡️ **Prévention** : Vermifuger tous les 3 mois, litière propre, éviter les excréments de poules sauvages\n📅 **Posologie** : Répéter après 10 jours";
$knowledge['vers intestinaux poulet'] = $knowledge['parasites internes'];

$knowledge['poux rouges'] = "🕷️ **POUX ROUGES**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🔍 **Particularités** : Parasites nocturnes (se cachent dans les fissures le jour)\n🩺 **Symptômes** : Agitation, ailes tombantes, pâleur de la crête et de la barbillon, baisse de ponte sévère\n💊 **Traitement** : Sprays à base de pyrèthre, Désinfection complète du poulailler (chaleur, eau bouillante)\n🛡️ **Prévention** : Nettoyage régulier, diatomées, huiles essentielles (tea tree, lavande)";

// ----- COMPORTEMENT -----
$knowledge['stress poulet'] = "😰 **STRESS CHEZ LE POULET**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🔴 **Signes** : Picage excessif, cannibalisme, plumes arrachées, baisse d'appétit, perte de poids, chute de ponte, tolérence au bruit\n💡 **Causes fréquentes** : Surpopulation, lumière trop forte ou trop faible, carence alimentaire, ennui, bruits forts, changement brutal\n✅ **Solutions** : Réduire la densité (max 10-15/m²), enrichir l'environnement (perchoirs, balles de foin), lumière douce (20 lux), eau fraîche, routine stable";

$knowledge['picage'] = "🪶 **PICAGE ET CANNIBALISME**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🔴 **Description** : Comportement anormal où les poulets se picorent mutuellement, parfois mortel\n🧐 **Causes** : Surpopulation (trop de poulets/m²), lumière trop forte, carence en protéines ou sel, ennui, chaleur\n💡 **Solutions** : Épointage du bec (si autorisé légalement), augmenter l'espace, distribuer du foin ou du chou à picorer, réduire l'intensité lumineuse, ajouter des coquilles d'huîtres";

$knowledge['isolement'] = "🤔 **POULET ISOLÉ DU GROUPE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🔍 **Interprétation** : Signe précoce de maladie (souvent dans les 24-48h avant les autres symptômes)\n✅ **Action recommandée** : Examinez immédiatement le poulet (état général, fientes, respiration, yeux). Isolez-le pour éviter la contagion. Observez les autres du groupe.\n📊 **À faire** : Noter le comportement pour le diagnostic IA";

$knowledge['perte appetit'] = "🍽️ **PERTE D'APPÉTIT**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🔴 **Causes possibles** :\n- Maladie (coccidiose, salmonellose, gumboro)\n- Stress thermique (trop chaud)\n- Eau sale ou absente\n- Aliment avarié\n💡 **Actions** : Vérifiez la température (32-35°C optimal), l'eau (fraîche, propre), les fientes. Consultez le diagnostic IA si persiste >24h.";

$knowledge['tremblements'] = "🌡️ **TREMBLEMENTS CHEZ LE POULET**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🔴 **Causes possibles** :\n- Fièvre (infection bactérienne/virale)\n- Carence en vitamine E ou B1\n- Empoisonnement (sel, moisissures)\n- Maladie de Newcastle (stade nerveux)\n💡 **Action urgente** : Isolez immédiatement, notez tous les symptômes (diarrhée, respiration,...) et consultez le diagnostic IA sans tarder.";

// ----- ALIMENTATION -----
$knowledge['alimentation poussin'] = "🍼 **ALIMENTATION DES POUSSINS (0-10 jours)**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🥄 **Type** : Aliment démarrage (farine fine ou microgranulés)\n⚡ **Protéines** : 22-24% (Croissance rapide)\n💧 **Eau** : 32-35°C les 2-3 premiers jours, puis température ambiante\n⏰ **Fréquence** : 5 à 6 petits repas par jour\n🚰 **Hydratation critique** : Ne jamais manquer d'eau";";

$knowledge['alimentation poulet chair'] = "🍗 **ALIMENTATION POULET DE CHAIR**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 **Phases** :\n┌─────────────┬────────────┬────────────────┐\n│ Phase       │ Âge (jours)│ Protéines (%)  │\n├─────────────┼────────────┼────────────────┤\n│ Démarrage   │ 0-10       │ 22-24          │\n│ Croissance  │ 11-30      │ 20-22          │\n│ Finition    │ 31-45      │ 18-20          │\n└─────────────┴────────────┴────────────────┘\n💧 Eau : À volonté, fraîche et propre en permanence\n📈 Résultat : Poids atteint 2.2-2.5 kg à 45 jours";

$knowledge['alimentation pondeuse'] = "🥚 **ALIMENTATION PONDEUSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 **Phases** :\n┌─────────────┬────────────┬────────────────┐\n│ Phase       │ Âge (sem)  │ Protéines (%)  │\n├─────────────┼────────────┼────────────────┤\n│ Démarrage   │ 0-6        │ 20             │\n│ Croissance  │ 7-18       │ 16-18          │\n│ Ponte       │ 18+        │ 16-17 + Calcium│\n└─────────────┴────────────┴────────────────┘\n🦪 **Calcium** : 3.5-4% (coquilles d'huîtres en libre-service)\n💡 **Astuce** : Lumière 14-16h/jour pour booster la ponte";

// ----- VACCINATION -----
$knowledge['calendrier vaccinal'] = "💉 **CALENDRIER VACCINAL STANDARD**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📆 **Planning recommandé** :\n┌──────────────┬────────────────────────┐\n│ Jour         │ Vaccin                 │\n├──────────────┼────────────────────────┤\n│ Jour 7       │ Gumboro                │\n│ Jour 14      │ Newcastle              │\n│ Jour 21      │ Bronchite infectieuse  │\n│ Jour 28      │ Variole aviaire        │\n│ Jour 35      │ Rappel Newcastle       │\n│ Jour 42      │ Rappel Bronchite       │\n└──────────────┴────────────────────────┘\n👨‍⚕️ **Important** : Consultez votre vétérinaire pour adapter à votre région et lot.";

// ----- TEMPÉRATURE -----
$knowledge['temperature'] = "🌡️ **TEMPÉRATURE IDÉALE PAR ÂGE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 **Consignes de chaleur** :\n┌─────────────┬────────────────────────┐\n│ Âge         │ Température            │
├─────────────┼────────────────────────┤
│ Semaine 1   │ 32-35°C                │
│ Semaine 2   │ 30-32°C                │
│ Semaine 3   │ 28-30°C                │
│ Semaine 4   │ 25-28°C                │
│ Semaine 5+  │ 21-24°C                │
└─────────────┴────────────────────────┘\n📉 **Réduction** : Baisser de 2-3°C par semaine\n👀 **Observation** : Si les poulets se rassemblent → trop froid ; s'ils sont écartés et haletants → trop chaud.";

// ----- HYGIENE -----
$knowledge['nettoyage'] = "🧹 **PROGRAMME DE NETTOYAGE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📅 **Fréquences** :\n- **Quotidien** : Eau fraîche, retrait des excréments visibles\n- **Hebdomadaire** : Nettoyage/désinfection mangeoires/abreuvoirs, changement litière humide, désinfection des surfaces\n- **Mensuel** : Désinfection complète à la chaux ou produit vétérinaire, vide sanitaire si possible\n🛠️ **Produits** : Virkon, chloramine T, eau de Javel diluée (rincer abondamment)";

// ----- RACES -----
$knowledge['poulet chair'] = "🍗 **POULET DE CHAIR**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 **Caractéristiques** :\n- Durée : 45 jours\n- Poids : 2.2-2.5 kg\n- Alimentation : riche en protéines\n- Fragilité : Sensible fin de cycle\n💰 **Rentabilité** : Élevage intensif, conversion alimentaire ~1.8";
$knowledge['pondeuse'] = "🥚 **PONDEUSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 **Caractéristiques** :\n- Début ponte : 18 semaines\n- Pic de ponte : 25-30 semaines\n- Production : ~260-300 œufs/an\n- Calcium : indispensable (3.5-4%)";
$knowledge['bresse'] = "👑 **POULET DE BRESSE (AOC)**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 **Particularités** :\n- Durée : 120 jours minimum\n- Poids : 2.2-2.5 kg\n- Élevage : Plein air obligatoire\n- Alimentation : Lait caillé en finition\n- Prix : Élevé, chair exceptionnelle";
$knowledge['goliath'] = "🦃 **GOLIATH**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 **Particularités** :\n- Durée : 42 jours seulement\n- Poids : 3.5-4.0 kg\n- Croissance : Extrêmement rapide\n- Risques : Problèmes de pattes (surveiller)";
$knowledge['cou nu'] = "🦴 **COU NU**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 **Particularités** :\n- Durée : 70-80 jours\n- Poids : 2.5-3.0 kg\n- Avantages : Supporte bien la chaleur\n- Rusticité : Excellente";
$knowledge['faverolles'] = "🐓 **FAVEROLLES**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 **Particularités** :\n- Double finition : Chair et œufs\n- Durée : 120 jours (ponte), 90 jours (chair)\n- Poids : 3.0-3.5 kg\n- Caractère : Douce";

// ----- DIVERS -----
$knowledge['mortalite normale'] = "⚰️ **MORTALITÉ NORMALE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n📊 **Seuils d'alerte** :\n┌─────────────┬────────────────────────┐\n│ Âge         │ Taux max acceptable    │
├─────────────┼────────────────────────┤
│ 0-7 jours   │ 1-2%                   │
│ 8-21 jours  │ 0.5-1% par semaine     │
│ Au-delà     │ <0.5% par semaine      │
└─────────────┴────────────────────────┘\n⚠️ **Alerte** : Au-delà de 2% en une journée → consulter immédiatement";

$knowledge['autopsie'] = "🔪 **AUTOPSIE DE POULET**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n🩺 **Points à observer** :\n- Apparence de la crête et barbillon\n- État du muscle (hémorragies)\n- Foie (taches blanchâtres → salmonellose)\n- Cœur (péricardite → colibacillose)\n- Intestins (points rouges → coccidiose)\n- Respiration (exsudat → mycoplasmose)\n⚠️ Protégez-vous (gants, lunettes) et désinfectez tout";

// ==================== FONCTION DE RECHERCHE INTELLIGENTE ====================
function searchInDatabase($keyword) {
    global $conn;
    // Rechercher dans les lots
    $stmt = $conn->prepare("SELECT name FROM batches WHERE name LIKE ? LIMIT 3");
    $stmt->execute(["%$keyword%"]);
    $batches = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($batches) {
        return "📁 **Lot(s) correspondant(s)** : " . implode(', ', $batches) . "\n\n👉 Vous pouvez consulter les détails dans votre tableau de bord.";
    }
    
    // Rechercher dans les maladies courantes
    $stmt = $conn->prepare("SELECT name FROM vaccines WHERE name LIKE ? LIMIT 2");
    $stmt->execute(["%$keyword%"]);
    $vaccines = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($vaccines) {
        return "💉 **Vaccin(s) trouvé(s)** : " . implode(', ', $vaccines) . "\n\n👉 Calendrier dans la page Vaccination.";
    }
    return null;
}

// ==================== ANALYSE ET RÉPONSE ====================
$response = null;

// 1. Recherche dans la base de connaissance
foreach ($knowledge as $key => $value) {
    if (strpos($message, $key) !== false) {
        $response = $value;
        break;
    }
}

// 2. Si rien trouvé, chercher des synonymes approximatifs
if (!$response) {
    $synonyms = [
        'diarrhée' => 'diarrhee', 'sang' => 'sanglante', 'respiration' => 'respiratoire',
        'toux' => 'tousse', 'mange pas' => 'perte appetit', 'isole' => 'isolement',
        'plume' => 'plumes herissees', 'tremble' => 'tremblements', 'faible' => 'fatigue'
    ];
    $modified = $message;
    foreach ($synonyms as $from => $to) {
        if (strpos($message, $from) !== false) {
            $modified = str_replace($from, $to, $modified);
            foreach ($knowledge as $key => $value) {
                if (strpos($modified, $key) !== false || strpos($key, $modified) !== false) {
                    $response = $value;
                    break 2;
                }
            }
        }
    }
}

// 3. Recherche dans la base de données SQL
if (!$response) {
    $fromDB = searchInDatabase($message);
    if ($fromDB) $response = $fromDB;
}

// 4. Fallback intelligent avec suggestions
if (!$response) {
    $response = "🤔 **Je n'ai pas encore appris cette question précise.**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n💡 **Suggestions pour trouver votre réponse** :\n\n📌 **Pages utiles** :\n• Diagnostic IA → pour les symptômes\n• Conseils préventifs → races/alimentation\n• Calendrier vaccinal\n• FAQ dans l'Aide\n\n🔍 **Reformulez votre question** :\nExemples de questions que je comprends :\n- \"Quels sont les symptômes de la coccidiose ?\"\n- \"Comment nourrir un poussin ?\"\n- \"Quelle température pour des poussins de 1 semaine ?\"\n- \"Calendrier vaccinal pour poulet\"\n\n📞 **Support** : Si vous ne trouvez pas, contactez votre vétérinaire.";
}

echo json_encode(['response' => $response]);
?>