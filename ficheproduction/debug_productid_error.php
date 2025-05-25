<?php
/**
 * Debug spécifique pour l'erreur "productId is not defined"
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
$id = GETPOST('id', 'int');
$token = newToken();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug erreur productId</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: #f9f9f9; }
        .debug-title { font-weight: bold; font-size: 16px; color: #333; margin-bottom: 10px; }
        .debug-content { background: #fff; padding: 10px; border-left: 3px solid #007cba; }
        .error { color: #d63638; font-weight: bold; }
        .success { color: #46b450; font-weight: bold; }
        .code-block { background: #f5f5f5; padding: 10px; border: 1px solid #ccc; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>

<h1>🔧 Debug erreur "productId is not defined"</h1>

<?php if (!$id): ?>
<div class="error">Veuillez spécifier l'ID de la commande : ?id=123</div>
<?php else: ?>

<div class="debug-section">
    <div class="debug-title">🎯 Diagnostic de l'erreur productId</div>
    <div class="debug-content">
        <p>L'erreur "productId is not defined" peut venir de plusieurs endroits. Testez cette page pour identifier le problème :</p>
        
        <h4>1. Test de chargement des données</h4>
        <button onclick="testLoadData()" style="background: #007cba; color: white; padding: 10px; border: none; cursor: pointer;">Tester chargement données</button>
        <div id="loadDataResult"></div>
        
        <h4>2. Test de structure des objets</h4>
        <button onclick="testObjectStructure()" style="background: #007cba; color: white; padding: 10px; border: none; cursor: pointer;">Tester structure objets</button>
        <div id="objectStructureResult"></div>
        
        <h4>3. Test de sauvegarde simulée</h4>
        <button onclick="testSaveSimulation()" style="background: #007cba; color: white; padding: 10px; border: none; cursor: pointer;">Tester sauvegarde simulée</button>
        <div id="saveSimulationResult"></div>
        
        <h4>4. Console debug</h4>
        <div id="debugConsole" style="background: #000; color: #0f0; padding: 10px; font-family: monospace; height: 200px; overflow-y: auto;"></div>
    </div>
</div>

<div class="debug-section">
    <div class="debug-title">💡 Corrections possibles</div>
    <div class="debug-content">
        <h4>Si l'erreur vient de prepareColisageDataForSave() :</h4>
        <div class="code-block">
// REMPLACER cette fonction dans ficheproduction.js :
function prepareColisageDataForSave() {
    return colis.map(c => ({
        number: c.number,
        maxWeight: c.maxWeight,
        totalWeight: c.totalWeight,
        multiple: c.multiple,
        status: c.status,
        isLibre: c.isLibre || false,
        products: c.products.map(p => {
            const product = products.find(prod => prod.id === p.productId);
            if (!product) {
                debugLog('❌ Produit non trouvé pour productId: ' + p.productId);
                return null;
            }

            if (product.isLibre) {
                return {
                    isLibre: true,
                    name: product.name,
                    description: '',
                    quantity: p.quantity,
                    weight: product.weight
                };
            } else {
                return {
                    isLibre: false,
                    productId: product.id,  // ← Utiliser product.id au lieu de p.productId
                    quantity: p.quantity,
                    weight: product.weight
                };
            }
        }).filter(p => p !== null)
    }));
}
        </div>
        
        <h4>Si l'erreur vient d'addProductToColis() :</h4>
        <div class="code-block">
// Vérifier que cette fonction utilise bien les bons IDs :
function addProductToColis(colisId, productId, quantity) {
    debugLog(`🔧 Ajout produit ${productId} (qté: ${quantity}) au colis ${colisId}`);
    
    const coliData = colis.find(c => c.id === colisId);
    const product = products.find(p => p.id === productId);
    
    if (!coliData || !product) {
        debugLog('ERREUR: Colis ou produit non trouvé');
        debugLog('coliData:', coliData);
        debugLog('product:', product);
        debugLog('productId recherché:', productId);
        debugLog('products disponibles:', products.map(p => ({id: p.id, name: p.name})));
        return;
    }
    
    // ... reste du code
}
        </div>
    </div>
</div>

<script>
const ORDER_ID = <?php echo $id; ?>;
const TOKEN = '<?php echo $token; ?>';

// Variables de test
let products = [];
let colis = [];

function debugLog(message) {
    console.log(message);
    const debugConsole = document.getElementById('debugConsole');
    if (debugConsole) {
        debugConsole.innerHTML += new Date().toLocaleTimeString() + ': ' + message + '<br>';
        debugConsole.scrollTop = debugConsole.scrollHeight;
    }
}

async function testLoadData() {
    const resultDiv = document.getElementById('loadDataResult');
    resultDiv.innerHTML = '<div style="color: orange;">🔄 Test en cours...</div>';
    
    try {
        debugLog('=== TEST CHARGEMENT DONNÉES ===');
        
        // Simuler l'appel AJAX
        const formData = new FormData();
        formData.append('action', 'ficheproduction_get_data');
        formData.append('token', TOKEN);
        formData.append('id', ORDER_ID);
        
        const response = await fetch(window.location.href.replace('debug_productid_error.php', 'ficheproduction.php'), {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        debugLog('Réponse brute: ' + text.substring(0, 200) + '...');
        
        const result = JSON.parse(text);
        
        if (result && result.products) {
            products = result.products;
            debugLog('✅ ' + products.length + ' produits chargés');
            
            // Analyser la structure des produits
            products.forEach((product, index) => {
                debugLog(`Produit ${index}: id=${product.id}, name=${product.name}, ref=${product.ref}`);
            });
            
            resultDiv.innerHTML = '<div style="color: green;">✅ Chargement réussi : ' + products.length + ' produits</div>';
        } else {
            debugLog('❌ Erreur dans la structure de réponse');
            resultDiv.innerHTML = '<div style="color: red;">❌ Erreur : structure de réponse invalide</div>';
        }
        
    } catch (error) {
        debugLog('❌ Erreur : ' + error.message);
        resultDiv.innerHTML = '<div style="color: red;">❌ Erreur : ' + error.message + '</div>';
    }
}

async function testObjectStructure() {
    const resultDiv = document.getElementById('objectStructureResult');
    
    if (products.length === 0) {
        resultDiv.innerHTML = '<div style="color: orange;">⚠️ Chargez d\'abord les données</div>';
        return;
    }
    
    debugLog('=== TEST STRUCTURE OBJETS ===');
    
    // Créer un colis de test
    const testColis = {
        id: 1,
        number: 1,
        products: [],
        totalWeight: 0,
        maxWeight: 25,
        status: 'ok',
        multiple: 1,
        isLibre: false
    };
    
    // Ajouter un produit au colis de test
    if (products.length > 0) {
        const firstProduct = products[0];
        testColis.products.push({
            productId: firstProduct.id,  // ← Utiliser firstProduct.id
            quantity: 5,
            weight: 12.5
        });
        
        debugLog('Produit ajouté au colis test:');
        debugLog('- productId: ' + firstProduct.id);
        debugLog('- name: ' + firstProduct.name);
        debugLog('- ref: ' + firstProduct.ref);
    }
    
    colis = [testColis];
    
    debugLog('Structure colis test:');
    debugLog(JSON.stringify(testColis, null, 2));
    
    resultDiv.innerHTML = '<div style="color: green;">✅ Structure créée avec succès</div>';
}

async function testSaveSimulation() {
    const resultDiv = document.getElementById('saveSimulationResult');
    
    if (colis.length === 0) {
        resultDiv.innerHTML = '<div style="color: orange;">⚠️ Testez d\'abord la structure des objets</div>';
        return;
    }
    
    debugLog('=== TEST SAUVEGARDE SIMULÉE ===');
    
    try {
        // Simuler prepareColisageDataForSave() avec debug
        const colisageData = colis.map(c => {
            debugLog('Traitement colis ID: ' + c.id);
            
            return {
                number: c.number,
                maxWeight: c.maxWeight,
                totalWeight: c.totalWeight,
                multiple: c.multiple,
                status: c.status,
                isLibre: c.isLibre || false,
                products: c.products.map(p => {
                    debugLog('Traitement produit dans colis:');
                    debugLog('- p.productId: ' + p.productId);
                    debugLog('- p.quantity: ' + p.quantity);
                    debugLog('- p.weight: ' + p.weight);
                    
                    const product = products.find(prod => {
                        debugLog('Comparaison: prod.id=' + prod.id + ' vs p.productId=' + p.productId);
                        return prod.id === p.productId;
                    });
                    
                    if (!product) {
                        debugLog('❌ Produit non trouvé pour productId: ' + p.productId);
                        debugLog('Produits disponibles: ' + products.map(pr => pr.id).join(', '));
                        return null;
                    }
                    
                    debugLog('✅ Produit trouvé: ' + product.name);
                    
                    if (product.isLibre) {
                        return {
                            isLibre: true,
                            name: product.name,
                            description: '',
                            quantity: p.quantity,
                            weight: product.weight
                        };
                    } else {
                        return {
                            isLibre: false,
                            productId: product.id,  // ← Utiliser product.id
                            quantity: p.quantity,
                            weight: product.weight
                        };
                    }
                }).filter(p => p !== null)
            };
        });
        
        debugLog('✅ Simulation réussie !');
        debugLog('Données préparées:');
        debugLog(JSON.stringify(colisageData, null, 2));
        
        resultDiv.innerHTML = '<div style="color: green;">✅ Simulation réussie ! Vérifiez la console debug.</div>';
        
    } catch (error) {
        debugLog('❌ Erreur dans la simulation: ' + error.message);
        debugLog('Stack trace: ' + error.stack);
        resultDiv.innerHTML = '<div style="color: red;">❌ Erreur : ' + error.message + '</div>';
    }
}
</script>

<?php endif; ?>

</body>
</html>