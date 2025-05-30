<?php
/**
 * \file       fix_path_pdf_v2.php
 * \ingroup    ficheproduction
 * \brief      Solution corrigée pour le chemin PDF avec détection automatique
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

echo "<h1>🔧 Correction du chemin PDF - Version 2</h1>";

// Charger la commande pour obtenir le bon chemin
$id = GETPOST('id', 'int');
if (empty($id)) $id = 11;

$object = new Commande($db);
$result = $object->fetch($id);

if ($result > 0) {
    echo "<h2>Informations de la commande</h2>";
    echo "ID: " . $object->id . "<br>";
    echo "Référence affichée: " . $object->ref . "<br>";
    
    // Obtenir le chemin de base des documents
    $baseDir = $conf->commande->multidir_output[$object->entity];
    echo "Répertoire de base: " . $baseDir . "<br>";
    
    echo "<h2>Recherche du vrai dossier</h2>";
    
    // Scanner le répertoire pour trouver les dossiers existants
    if (file_exists($baseDir) && is_dir($baseDir)) {
        $dirs = scandir($baseDir);
        $foundDirs = array();
        
        foreach ($dirs as $dir) {
            if ($dir != '.' && $dir != '..' && is_dir($baseDir . '/' . $dir)) {
                $foundDirs[] = $dir;
                echo "📁 Trouvé: " . $dir . "<br>";
            }
        }
        
        // Chercher le dossier qui correspond à notre commande
        $realDir = null;
        $linkDir = $baseDir . "/" . $object->ref;
        
        // Pattern pour les références Saphir (yy_mm_nnn)
        foreach ($foundDirs as $dir) {
            if (preg_match('/^\d{2}_\d{2}_\d{3}$/', $dir)) {
                echo "🎯 Dossier Saphir détecté: " . $dir . "<br>";
                $realDir = $baseDir . "/" . $dir;
                break;
            }
        }
        
        // Si pas trouvé par pattern, chercher par contenu
        if (!$realDir) {
            foreach ($foundDirs as $dir) {
                $testPath = $baseDir . "/" . $dir;
                $files = glob($testPath . "/*");
                if (!empty($files)) {
                    echo "📄 Dossier avec contenu: " . $dir . " (" . count($files) . " fichiers)<br>";
                    // Utiliser le premier dossier avec contenu
                    if (!$realDir) {
                        $realDir = $testPath;
                    }
                }
            }
        }
        
        if ($realDir) {
            echo "<h2>✅ Dossier source trouvé</h2>";
            echo "Dossier source: " . $realDir . "<br>";
            echo "Dossier cible: " . $linkDir . "<br>";
            
            // Créer le lien symbolique
            if (!file_exists($linkDir)) {
                if (symlink($realDir, $linkDir)) {
                    echo "✅ Lien symbolique créé avec succès !<br>";
                    
                    // Vérifier les permissions
                    if (is_writable($linkDir)) {
                        echo "✅ Le dossier lié est accessible en écriture<br>";
                    } else {
                        echo "⚠️ Le dossier lié n'est pas accessible en écriture<br>";
                    }
                    
                } else {
                    echo "❌ Impossible de créer le lien symbolique<br>";
                    echo "Erreur système: " . error_get_last()['message'] . "<br>";
                    
                    // Alternative: copier le dossier
                    echo "<h3>Alternative: Création du dossier</h3>";
                    if (mkdir($linkDir, 0771, true)) {
                        echo "✅ Dossier créé: " . $linkDir . "<br>";
                    } else {
                        echo "❌ Impossible de créer le dossier<br>";
                    }
                }
            } else {
                echo "ℹ️ Le lien existe déjà<br>";
            }
            
            // Test final
            echo "<h2>🧪 Test de génération PDF</h2>";
            echo '<a href="generate_pdf.php?id=' . $id . '&action=builddoc" target="_blank" style="display: inline-block; padding: 10px; background: #007cba; color: white; text-decoration: none; border-radius: 5px;">🔗 Générer le PDF maintenant</a><br><br>';
            
        } else {
            echo "❌ Aucun dossier source trouvé<br>";
        }
        
    } else {
        echo "❌ Le répertoire de base n'existe pas: " . $baseDir . "<br>";
    }
    
} else {
    echo "❌ Impossible de charger la commande ID: " . $id . "<br>";
}

echo "<h2>🔧 Debug des chemins</h2>";
echo "Chemin des documents Dolibarr: " . $conf->commande->dir_output . "<br>";
echo "Chemin multi-entité: " . $conf->commande->multidir_output[$object->entity ?? 1] . "<br>";
echo "Entité courante: " . ($object->entity ?? 1) . "<br>";
