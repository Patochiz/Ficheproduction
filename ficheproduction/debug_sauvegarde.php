<?php
/**
 * Fichier de debug pour diagnostiquer le problème de sauvegarde
 * À uploader dans le dossier racine de votre module ficheproduction
 * Accédez-y via: http://votre-site.com/custom/ficheproduction/debug_sauvegarde.php?id=NUMERO_COMMANDE
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

// Désactiver temporairement la protection CSRF pour ce fichier de debug
$dolibarr_nocsrfcheck = 1;

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

// Générer un token pour les formulaires
$token = newToken();

// CSS pour un affichage propre
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Sauvegarde Colisage</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: #f9f9f9; }
        .debug-title { font-weight: bold; font-size: 16px; color: #333; margin-bottom: 10px; }
        .debug-content { background: #fff; padding: 10px; border-left: 3px solid #007cba; }
        .json-display { background: #f5f5f5; padding: 10px; border: 1px solid #ccc; white-space: pre-wrap; font-family: monospace; max-height: 300px; overflow-y: auto; }
        .error { color: #d63638; font-weight: bold; }
        .success { color: #46b450; font-weight: bold; }
        .warning { color: #ffb900; font-weight: bold; }
        .test-button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; }
        .test-button:hover { background: #005a87; }
    </style>
</head>
<body>

<h1>🔧 Debug Sauvegarde Colisage</h1>

<?php

if (!$id) {
    echo '<div class="error">Veuillez spécifier l\'ID de la commande dans l\'URL : ?id=123</div>';
    echo '<p>Exemple : <code>debug_sauvegarde.php?id=123</code></p>';
    exit;
}

echo "<div class='debug-section'>";
echo "<div class='debug-title'>📋 Informations de base</div>";
echo "<div class='debug-content'>";
echo "ID Commande: <strong>$id</strong><br>";
echo "Action: <strong>" . ($action ? $action : 'Aucune') . "</strong><br>";
echo "Method: <strong>" . $_SERVER['REQUEST_METHOD'] . "</strong><br>";
echo "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "<br>";
echo "</div>";
echo "</div>";

// Traitement des actions de test
if ($action === 'test_json_simple') {
    echo "<div class='debug-section'>";
    echo "<div class='debug-title'>🧪 Test JSON Simple</div>";
    echo "<div class='debug-content'>";
    
    $testData = [
        'test' => 'simple',
        'number' => 123,
        'array' => ['a', 'b', 'c']
    ];
    
    $jsonString = json_encode($testData);
    echo "JSON généré: <div class='json-display'>$jsonString</div>";
    
    // Test via GETPOST alpha
    $recovered_alpha = GETPOST('test_json', 'alpha');
    echo "Récupéré via GETPOST alpha: <div class='json-display'>" . htmlspecialchars($recovered_alpha) . "</div>";
    
    // Test via GETPOST restricthtml
    $recovered_restricthtml = GETPOST('test_json', 'restricthtml');
    echo "Récupéré via GETPOST restricthtml: <div class='json-display'>" . htmlspecialchars($recovered_restricthtml) . "</div>";
    
    // Test via $_POST direct
    $recovered_post = $_POST['test_json'] ?? '';
    echo "Récupéré via \$_POST direct: <div class='json-display'>" . htmlspecialchars($recovered_post) . "</div>";
    
    // Test de décodage
    if ($recovered_alpha) {
        $decoded = json_decode($recovered_alpha, true);
        $jsonError = json_last_error();
        
        if ($jsonError === JSON_ERROR_NONE) {
            echo "<div class='success'>✅ Décodage JSON réussi</div>";
            echo "<pre>" . print_r($decoded, true) . "</pre>";
        } else {
            echo "<div class='error'>❌ Erreur JSON: " . json_last_error_msg() . "</div>";
        }
    }
    
    echo "</div>";
    echo "</div>";
}

if ($action === 'test_json_complex') {
    echo "<div class='debug-section'>";
    echo "<div class='debug-title'>🧪 Test JSON Complexe (comme colisage)</div>";
    echo "<div class='debug-content'>";
    
    $complexData = [
        [
            'number' => 1,
            'maxWeight' => 25.0,
            'totalWeight' => 15.5,
            'multiple' => 2,
            'status' => 'ok',
            'isLibre' => false,
            'products' => [
                [
                    'isLibre' => false,
                    'productId' => 123,
                    'quantity' => 5,
                    'weight' => 12.5
                ],
                [
                    'isLibre' => true,
                    'name' => 'Échantillon test avec "guillemets" et caractères spéciaux: àéèù',
                    'description' => '',
                    'quantity' => 1,
                    'weight' => 0.5
                ]
            ]
        ]
    ];
    
    $jsonString = json_encode($complexData, JSON_UNESCAPED_UNICODE);
    echo "JSON complexe généré: <div class='json-display'>" . htmlspecialchars($jsonString) . "</div>";
    
    // Test des différentes méthodes de récupération
    $methods = ['alpha', 'restricthtml', 'none'];
    
    foreach ($methods as $method) {
        echo "<h4>Test avec GETPOST('test_json_complex', '$method'):</h4>";
        
        if ($method === 'none') {
            $recovered = $_POST['test_json_complex'] ?? '';
        } else {
            $recovered = GETPOST('test_json_complex', $method);
        }
        
        echo "Données récupérées: <div class='json-display'>" . htmlspecialchars($recovered) . "</div>";
        
        if ($recovered) {
            $decoded = json_decode($recovered, true);
            $jsonError = json_last_error();
            
            if ($jsonError === JSON_ERROR_NONE) {
                echo "<div class='success'>✅ Décodage JSON réussi avec méthode '$method'</div>";
            } else {
                echo "<div class='error'>❌ Erreur JSON avec méthode '$method': " . json_last_error_msg() . "</div>";
            }
        }
        echo "<hr>";
    }
    
    echo "</div>";
    echo "</div>";
}

if ($action === 'test_real_save') {
    echo "<div class='debug-section'>";
    echo "<div class='debug-title'>🎯 Test Sauvegarde Réelle</div>";
    echo "<div class='debug-content'>";
    
    echo "Simulation de l'appel de sauvegarde réel...<br>";
    
    // Récupération des données exactement comme dans le code original
    $colisData = GETPOST('colis_data', 'alpha');
    echo "Données reçues via GETPOST('colis_data', 'alpha'):<br>";
    echo "<div class='json-display'>" . htmlspecialchars($colisData) . "</div>";
    
    if (empty($colisData)) {
        echo "<div class='error'>❌ Aucune donnée reçue</div>";
    } else {
        // Test du décodage comme dans le code original
        $decodedData = json_decode($colisData, true);
        $jsonError = json_last_error();
        
        if ($jsonError !== JSON_ERROR_NONE || !$decodedData || !is_array($decodedData)) {
            echo "<div class='error'>❌ Erreur de décodage JSON</div>";
            echo "Code d'erreur: $jsonError<br>";
            echo "Message d'erreur: " . json_last_error_msg() . "<br>";
            
            // Diagnostic détaillé
            echo "<h4>🔍 Diagnostic détaillé:</h4>";
            echo "Longueur des données: " . strlen($colisData) . " caractères<br>";
            echo "Premier caractère: '" . (strlen($colisData) > 0 ? $colisData[0] : 'N/A') . "'<br>";
            echo "Dernier caractère: '" . (strlen($colisData) > 0 ? $colisData[strlen($colisData)-1] : 'N/A') . "'<br>";
            
            // Vérification des caractères problématiques
            $problematicChars = ['\\', '"', "'", '&quot;', '&amp;', '&lt;', '&gt;'];
            foreach ($problematicChars as $char) {
                if (strpos($colisData, $char) !== false) {
                    echo "<div class='warning'>⚠️ Caractère problématique trouvé: '$char'</div>";
                }
            }
        } else {
            echo "<div class='success'>✅ Décodage JSON réussi !</div>";
            echo "Nombre d'éléments: " . count($decodedData) . "<br>";
            echo "<pre>" . print_r($decodedData, true) . "</pre>";
        }
    }
    
    echo "</div>";
    echo "</div>";
}

?>

<div class="debug-section">
    <div class="debug-title">🧪 Tests disponibles</div>
    <div class="debug-content">
        <p>Cliquez sur les boutons ci-dessous pour tester différents aspects de la transmission JSON :</p>
        
        <!-- Test JSON Simple -->
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="test_json_simple">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <input type="hidden" name="test_json" value='{"test":"simple","number":123,"array":["a","b","c"]}'>
            <button type="submit" class="test-button">Test JSON Simple</button>
        </form>
        
        <!-- Test JSON Complexe -->
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="test_json_complex">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <input type="hidden" name="test_json_complex" value='[{"number":1,"maxWeight":25,"totalWeight":15.5,"multiple":2,"status":"ok","isLibre":false,"products":[{"isLibre":false,"productId":123,"quantity":5,"weight":12.5},{"isLibre":true,"name":"Échantillon test avec \"guillemets\" et caractères spéciaux: àéèù","description":"","quantity":1,"weight":0.5}]}]'>
            <button type="submit" class="test-button">Test JSON Complexe</button>
        </form>
        
        <br><br>
        
        <!-- Simulation via JavaScript (comme l'original) -->
        <button class="test-button" onclick="testRealScenario()">Test Scénario Réel (via JS)</button>
        
        <div id="jsTestResult" style="margin-top: 10px;"></div>
    </div>
</div>

<div class="debug-section">
    <div class="debug-title">💡 Solutions recommandées</div>
    <div class="debug-content">
        <h4>Si vous obtenez des erreurs JSON, voici les solutions :</h4>
        
        <p><strong>1. Changement du paramètre GETPOST :</strong></p>
        <p>Dans <code>ficheproduction.php</code>, ligne ~160, remplacez :</p>
        <code>$colisData = GETPOST('colis_data', 'alpha');</code>
        <p>Par :</p>
        <code>$colisData = GETPOST('colis_data', 'restricthtml');</code>
        <p>Ou mieux encore :</p>
        <code>$colisData = $_POST['colis_data'] ?? '';</code>
        
        <p><strong>2. Encodage des données côté JavaScript :</strong></p>
        <p>Dans <code>ficheproduction.js</code>, fonction <code>apiCall</code>, essayez d'encoder les données :</p>
        <code>formData.append(key, encodeURIComponent(value));</code>
        <p>Et décoder côté PHP :</p>
        <code>$colisData = urldecode(GETPOST('colis_data', 'alpha'));</code>
        
        <p><strong>3. Utilisation de base64 :</strong></p>
        <p>Encoder en base64 côté JS puis décoder côté PHP pour éviter tous les problèmes d'échappement.</p>
    </div>
</div>

<script>
async function testRealScenario() {
    const resultDiv = document.getElementById('jsTestResult');
    resultDiv.innerHTML = '<div class="warning">🔄 Test en cours...</div>';
    
    try {
        // Simuler exactement les données comme dans le vrai scénario
        const testData = [{
            number: 1,
            maxWeight: 25.0,
            totalWeight: 15.5,
            multiple: 2,
            status: 'ok',
            isLibre: false,
            products: [{
                isLibre: false,
                productId: 123,
                quantity: 5,
                weight: 12.5
            }, {
                isLibre: true,
                name: 'Échantillon test avec "guillemets" et caractères spéciaux: àéèù',
                description: '',
                quantity: 1,
                weight: 0.5
            }]
        }];
        
        // Envoyer via FormData comme dans le vrai code
        const formData = new FormData();
        formData.append('action', 'test_real_save');
        formData.append('id', '<?php echo $id; ?>');
        formData.append('token', '<?php echo $token; ?>');
        formData.append('colis_data', JSON.stringify(testData));
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        
        // Afficher la réponse dans une nouvelle fenêtre pour éviter de recharger la page
        const newWindow = window.open('', '_blank');
        newWindow.document.write(text);
        
        resultDiv.innerHTML = '<div class="success">✅ Test envoyé ! Vérifiez la nouvelle fenêtre.</div>';
        
    } catch (error) {
        resultDiv.innerHTML = '<div class="error">❌ Erreur: ' + error.message + '</div>';
    }
}
</script>

</body>
</html>