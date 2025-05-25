<?php
/**
 * Debug spécifique pour le mapping productId et le problème ref_ligne
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

$dolibarr_nocsrfcheck = 1;

require_once dol_buildpath('/ficheproduction/class/ficheproductionmanager.class.php');
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$token = newToken();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Mapping ProductId et ref_ligne</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: #f9f9f9; }
        .debug-title { font-weight: bold; font-size: 16px; color: #333; margin-bottom: 10px; }
        .debug-content { background: #fff; padding: 10px; border-left: 3px solid #007cba; }
        .error { color: #d63638; font-weight: bold; }
        .success { color: #46b450; font-weight: bold; }
        .warning { color: #ffb900; font-weight: bold; }
        .test-button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; }
        .mapping-table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        .mapping-table th, .mapping-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .mapping-table th { background-color: #f2f2f2; }
        .highlight { background-color: #fff3cd; }
    </style>
</head>
<body>

<h1>🔧 Debug Mapping ProductId et ref_ligne</h1>

<?php

if (!$id) {
    echo '<div class="error">Veuillez spécifier l\'ID de la commande : ?id=123</div>';
    exit;
}

// Charger la commande
$object = new Commande($db);
$result = $object->fetch($id);
$object->fetch_lines();

echo "<div class='debug-section'>";
echo "<div class='debug-title'>📋 Mapping JavaScript ↔ Base de données</div>";
echo "<div class='debug-content'>";

echo "<p><strong>Problème identifié :</strong> Le JavaScript utilise des IDs séquentiels (1, 2, 3...) mais la base utilise les vrais IDs produits (296, 297, 298...)</p>";

echo "<table class='mapping-table'>";
echo "<tr><th>Index JS</th><th>ID Produit DB</th><th>Ref Produit</th><th>ref_ligne</th><th>Quantité (nombre)</th><th>Status</th></tr>";

$jsIndex = 1;
foreach ($object->lines as $lineIndex => $line) {
    if ($line->fk_product > 0) {
        $product = new Product($db);
        $product->fetch($line->fk_product);
        
        // Récupérer la quantité depuis extrafield nombre
        $quantity = 0;
        if (isset($line->array_options['options_nombre']) && !empty($line->array_options['options_nombre'])) {
            $quantity = intval($line->array_options['options_nombre']);
        } else {
            $quantity = intval($line->qty);
        }
        
        // Skip si quantité = 0
        if ($quantity <= 0) {
            continue;
        }
        
        $ref_ligne = $line->array_options['options_ref_ligne'] ?? '';
        $rowClass = (!empty($ref_ligne)) ? 'highlight' : '';
        $status = (!empty($ref_ligne)) ? '⚠️ PROBLÉMATIQUE' : '✅ OK';
        
        echo "<tr class='$rowClass'>";
        echo "<td><strong>$jsIndex</strong></td>";
        echo "<td>{$line->fk_product}</td>";
        echo "<td>{$product->ref}</td>";
        echo "<td>" . htmlspecialchars($ref_ligne) . "</td>";
        echo "<td>$quantity</td>";
        echo "<td>$status</td>";
        echo "</tr>";
        
        $jsIndex++;
    }
}

echo "</table>";
echo "</div></div>";

if ($action === 'test_fix_mapping') {
    echo "<div class='debug-section'>";
    echo "<div class='debug-title'>🧪 Test avec Mapping Corrigé</div>";
    echo "<div class='debug-content'>";
    
    // Récupérer les données corrigées
    $colisData = GETPOST('colis_data', 'restricthtml');
    echo "Données avec mapping corrigé :<br>";
    echo "<pre>" . htmlspecialchars($colisData) . "</pre>";
    
    $decodedData = json_decode($colisData, true);
    
    if ($decodedData) {
        echo "<div class='success'>✅ JSON décodé</div>";
        
        // Vérifier le mapping
        foreach ($decodedData as $colisIndex => $colis) {
            echo "<h4>Colis $colisIndex:</h4>";
            foreach ($colis['products'] as $productIndex => $productData) {
                $productId = $productData['productId'];
                
                // Chercher le produit correspondant
                $found = false;
                foreach ($object->lines as $line) {
                    if ($line->fk_product == $productId) {
                        $found = true;
                        $ref_ligne = $line->array_options['options_ref_ligne'] ?? '';
                        echo "  - Produit ID $productId: ✅ Trouvé dans la commande";
                        if (!empty($ref_ligne)) {
                            echo " (ref_ligne: '" . htmlspecialchars($ref_ligne) . "')";
                        }
                        echo "<br>";
                        break;
                    }
                }
                
                if (!$found) {
                    echo "  - Produit ID $productId: ❌ NON TROUVÉ dans la commande<br>";
                }
            }
        }
        
        // Test de sauvegarde
        echo "<h4>Test de sauvegarde:</h4>";
        try {
            $manager = new FicheProductionManager($db);
            $result = $manager->saveColisageData($object->id, $object->socid, $decodedData, $user);
            
            echo "<pre>" . print_r($result, true) . "</pre>";
            
            if ($result['success']) {
                echo "<div class='success'>✅ SAUVEGARDE RÉUSSIE!</div>";
            } else {
                echo "<div class='error'>❌ Erreur: " . htmlspecialchars($result['error'] ?? $result['message']) . "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "</div></div>";
}

// Test avec le produit problématique spécifique
if ($action === 'test_problematic_product') {
    echo "<div class='debug-section'>";
    echo "<div class='debug-title'>🎯 Test Produit Problématique (ref_ligne = 'Test 1')</div>";
    echo "<div class='debug-content'>";
    
    // Créer un colis avec le produit qui a ref_ligne = "Test 1"
    $problematicProductId = 296; // Premier produit avec ref_ligne
    
    $testData = [
        [
            'number' => 1,
            'maxWeight' => 25.0,
            'totalWeight' => 15.5,
            'multiple' => 1,
            'status' => 'ok',
            'isLibre' => false,
            'products' => [
                [
                    'isLibre' => false,
                    'productId' => $problematicProductId,
                    'quantity' => 5,
                    'weight' => 12.5
                ]
            ]
        ]
    ];
    
    echo "Test avec le produit ID $problematicProductId (celui qui a ref_ligne = 'Test 1'):<br>";
    echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";
    
    try {
        $manager = new FicheProductionManager($db);
        
        // Activer le debug SQL
        $db->debug = true;
        
        echo "<h4>Tentative de sauvegarde...</h4>";
        $result = $manager->saveColisageData($object->id, $object->socid, $testData, $user);
        
        echo "Résultat:<br>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        
        if ($result['success']) {
            echo "<div class='success'>✅ SAUVEGARDE RÉUSSIE avec le produit problématique!</div>";
        } else {
            echo "<div class='error'>❌ Échec avec le produit problématique</div>";
            echo "Erreur: " . htmlspecialchars($result['error'] ?? $result['message']) . "<br>";
            
            // Analyser les erreurs SQL éventuelles
            if ($db->lasterror()) {
                echo "Dernière erreur SQL: " . htmlspecialchars($db->lasterror()) . "<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Exception avec produit problématique: " . $e->getMessage() . "</div>";
        echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    echo "</div></div>";
}

?>

<div class="debug-section">
    <div class="debug-title">🧪 Tests de Correction</div>
    <div class="debug-content">
        
        <h4>1. Test avec mapping corrigé (IDs réels)</h4>
        <form method="POST">
            <input type="hidden" name="action" value="test_fix_mapping">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <textarea name="colis_data" rows="15" cols="80">[
    {
        "number": 1,
        "maxWeight": 25.0,
        "totalWeight": 15.5,
        "multiple": 1,
        "status": "ok",
        "isLibre": false,
        "products": [
            {
                "isLibre": false,
                "productId": 296,
                "quantity": 5,
                "weight": 12.5
            }
        ]
    }
]</textarea><br>
            <button type="submit" class="test-button">Tester avec ID Réel (296)</button>
        </form>
        
        <h4>2. Test spécifique du produit problématique</h4>
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="test_problematic_product">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <button type="submit" class="test-button">Tester Produit avec ref_ligne = "Test 1"</button>
        </form>
    </div>
</div>

<div class="debug-section">
    <div class="debug-title">💡 Solutions à implémenter</div>
    <div class="debug-content">
        <h4>1. Fix du mapping JavaScript → PHP</h4>
        <p>Dans <code>ficheproduction.js</code>, fonction <code>addProductToColis</code>, le <code>productId</code> envoyé doit être le vrai ID de la ligne de commande, pas l'index JavaScript.</p>
        
        <h4>2. Fix du problème ref_ligne</h4>
        <p>Dans les classes FicheProduction, s'assurer que les champs avec des valeurs non-vides sont correctement échappés avant insertion SQL.</p>
        
        <h4>3. Amélioration de la gestion d'erreurs</h4>
        <p>Récupérer et afficher les vrais messages d'erreur au lieu de messages vides.</p>
    </div>
</div>

</body>
</html>