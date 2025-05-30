<?php
/**
 * \file       ficheproductionpdf_fix_final.php
 * \ingroup    ficheproduction
 * \brief      Solution finale pour corriger le problème de chemin + classe PDF complète
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

echo "<h1>🔧 Solution Finale - Correction Complète</h1>";

$id = GETPOST('id', 'int');
if (empty($id)) $id = 11;

$object = new Commande($db);
$result = $object->fetch($id);

if ($result > 0) {
    echo "<h2>✅ Commande chargée</h2>";
    echo "ID: " . $object->id . "<br>";
    echo "Référence: " . $object->ref . "<br>";
    
    // Le problème : avec Saphir, la référence EST le nom du dossier
    // Donc pas besoin de lien symbolique !
    
    $baseDir = $conf->commande->multidir_output[$object->entity];
    $realDir = $baseDir . "/" . $object->ref;  // 25_04_003
    
    echo "Dossier attendu: " . $realDir . "<br>";
    echo "Existe: " . (file_exists($realDir) ? "✅ Oui" : "❌ Non") . "<br>";
    echo "Accessible en écriture: " . (is_writable($realDir) ? "✅ Oui" : "❌ Non") . "<br>";
    
    if (file_exists($realDir)) {
        echo "<h2>📁 Contenu du dossier</h2>";
        $files = scandir($realDir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "📄 " . $file . "<br>";
            }
        }
        
        echo "<h2>🧪 Test de la classe PDF</h2>";
        
        // Test de chargement de la classe
        try {
            require_once dol_buildpath('/ficheproduction/class/ficheproductionpdf.class.php');
            echo "✅ Classe PDF chargée<br>";
            
            $pdfGenerator = new FicheProductionPDF($db);
            echo "✅ Instance PDF créée<br>";
            
            // Test de génération (version simple)
            echo "<h3>Test de génération...</h3>";
            
            $result = $pdfGenerator->write_file($object, $langs);
            
            if ($result > 0) {
                echo "✅ PDF généré avec succès !<br>";
                
                $filename = $object->ref."-fiche-production.pdf";
                $filepath = $realDir . "/" . $filename;
                
                if (file_exists($filepath)) {
                    echo "✅ Fichier créé: " . $filename . "<br>";
                    echo "Taille: " . filesize($filepath) . " octets<br>";
                    echo '<a href="../generate_pdf.php?id='.$id.'&action=builddoc" target="_blank">🔗 Voir le PDF</a><br>';
                } else {
                    echo "❌ Fichier non trouvé après génération<br>";
                }
                
            } else {
                echo "❌ Erreur de génération: " . $pdfGenerator->error . "<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Erreur classe PDF: " . $e->getMessage() . "<br>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        
    } else {
        echo "❌ Le dossier n'existe pas<br>";
    }
    
} else {
    echo "❌ Impossible de charger la commande<br>";
}

echo "<h2>🔧 Actions recommandées</h2>";
echo "<ol>";
echo "<li><strong>Vérifiez que le fichier ficheproductionpdf.class.php est complet</strong> (pas tronqué)</li>";
echo "<li><strong>Vérifiez les permissions</strong> du dossier 25_04_003 (doit être 755 ou 771)</li>";
echo "<li><strong>Consultez les logs</strong> pour voir l'erreur exacte</li>";
echo "</ol>";

echo "<h2>📋 Informations pour le debug</h2>";
echo "Version PHP: " . PHP_VERSION . "<br>";
echo "Mémoire disponible: " . ini_get('memory_limit') . "<br>";
echo "Erreurs PHP activées: " . (ini_get('display_errors') ? 'Oui' : 'Non') . "<br>";
