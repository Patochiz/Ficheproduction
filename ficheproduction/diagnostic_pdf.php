<?php
/**
 * \file       diagnostic_pdf.php
 * \ingroup    ficheproduction
 * \brief      Diagnostic pour identifier les problèmes PDF
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

// Force error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Diagnostic PDF - Fiche de Production</h1>";

echo "<h2>1. Vérification de l'environnement</h2>";

// Test basic includes
try {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
    echo "✅ order.lib.php chargé<br>";
} catch (Exception $e) {
    echo "❌ Erreur order.lib.php: " . $e->getMessage() . "<br>";
}

try {
    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
    echo "✅ commande.class.php chargé<br>";
} catch (Exception $e) {
    echo "❌ Erreur commande.class.php: " . $e->getMessage() . "<br>";
}

try {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
    echo "✅ pdf.lib.php chargé<br>";
} catch (Exception $e) {
    echo "❌ Erreur pdf.lib.php: " . $e->getMessage() . "<br>";
}

echo "<h2>2. Test TCPDF</h2>";

try {
    $pdf = pdf_getInstance('', 'mm', 'A4');
    echo "✅ TCPDF initialisé avec succès<br>";
    echo "Type: " . get_class($pdf) . "<br>";
} catch (Exception $e) {
    echo "❌ Erreur TCPDF: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Test classe FicheProductionPDF</h2>";

try {
    require_once dol_buildpath('/ficheproduction/class/ficheproductionpdf.class.php');
    echo "✅ ficheproductionpdf.class.php chargé<br>";
    
    $pdfGenerator = new FicheProductionPDF($db);
    echo "✅ Instance FicheProductionPDF créée<br>";
} catch (Exception $e) {
    echo "❌ Erreur classe PDF: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Test FicheProductionManager</h2>";

try {
    require_once dol_buildpath('/ficheproduction/class/ficheproductionmanager.class.php');
    echo "✅ ficheproductionmanager.class.php chargé<br>";
    
    $manager = new FicheProductionManager($db);
    echo "✅ Instance FicheProductionManager créée<br>";
} catch (Exception $e) {
    echo "❌ Erreur manager: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Test commande</h2>";

$id = GETPOST('id', 'int');
if (empty($id)) $id = 11; // ID par défaut depuis votre URL

try {
    $object = new Commande($db);
    $result = $object->fetch($id);
    
    if ($result > 0) {
        echo "✅ Commande $id chargée: " . $object->ref . "<br>";
        echo "Client: " . $object->thirdparty->name . "<br>";
        echo "Nombre de lignes: " . count($object->lines) . "<br>";
    } else {
        echo "❌ Impossible de charger la commande $id<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur chargement commande: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Test dossiers et permissions</h2>";

try {
    $dir = $conf->commande->multidir_output[$object->entity ?? 1];
    echo "Dossier base: $dir<br>";
    
    if (file_exists($dir)) {
        echo "✅ Dossier base existe<br>";
        
        if (is_writable($dir)) {
            echo "✅ Dossier base accessible en écriture<br>";
        } else {
            echo "❌ Dossier base non accessible en écriture<br>";
        }
    } else {
        echo "❌ Dossier base n'existe pas<br>";
    }
    
    if (isset($object) && $object->ref) {
        $commandeDir = $dir."/".$object->ref;
        echo "Dossier commande: $commandeDir<br>";
        
        if (file_exists($commandeDir)) {
            echo "✅ Dossier commande existe<br>";
        } else {
            echo "⚠️ Dossier commande n'existe pas (sera créé)<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur dossiers: " . $e->getMessage() . "<br>";
}

echo "<h2>7. Test génération PDF simple</h2>";

try {
    if (isset($object) && $object->id > 0) {
        echo "Tentative de génération PDF...<br>";
        
        $pdfGenerator = new FicheProductionPDF($db);
        $result = $pdfGenerator->write_file($object, $langs);
        
        if ($result > 0) {
            echo "✅ PDF généré avec succès!<br>";
            
            $filename = $object->ref."-fiche-production.pdf";
            $filepath = $conf->commande->multidir_output[$object->entity]."/".$object->ref."/".$filename;
            
            if (file_exists($filepath)) {
                echo "✅ Fichier PDF créé: $filepath<br>";
                echo "Taille: " . filesize($filepath) . " octets<br>";
                echo '<a href="../generate_pdf.php?id='.$object->id.'&action=builddoc" target="_blank">🔗 Voir le PDF</a><br>';
            } else {
                echo "❌ Fichier PDF non trouvé après génération<br>";
            }
        } else {
            echo "❌ Erreur génération PDF: " . $pdfGenerator->error . "<br>";
        }
    } else {
        echo "⚠️ Pas de commande valide pour tester la génération<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception génération PDF: " . $e->getMessage() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>8. Logs système</h2>";

echo "Vérifiez ces fichiers de logs pour plus de détails :<br>";
echo "- /var/log/dolibarr.log<br>";
echo "- /var/log/apache2/error.log (ou nginx)<br>";
echo "- /var/log/php_errors.log<br>";

echo "<hr>";
echo "<p><strong>URL de test :</strong> <a href='?id=$id'>diagnostic_pdf.php?id=$id</a></p>";
echo "<p><strong>Note :</strong> Placez ce fichier dans le dossier ficheproduction/ et appelez-le via votre navigateur.</p>";
