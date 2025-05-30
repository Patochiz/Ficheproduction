<?php
/**
 * \file       path_debug.php
 * \ingroup    ficheproduction
 * \brief      Debug pour comprendre les chemins de fichiers
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

$id = GETPOST('id', 'int');
if (empty($id)) $id = 11;

echo "<h1>🔍 Debug Chemins de Fichiers</h1>";

$object = new Commande($db);
$result = $object->fetch($id);

if ($result > 0) {
    echo "<h2>Informations Commande</h2>";
    echo "ID: " . $object->id . "<br>";
    echo "Référence (ref): " . $object->ref . "<br>";
    echo "Référence externe (ref_ext): " . ($object->ref_ext ?? 'Non défini') . "<br>";
    echo "Référence client (ref_client): " . ($object->ref_client ?? 'Non défini') . "<br>";
    
    echo "<h2>Chemins calculés</h2>";
    $dir = $conf->commande->multidir_output[$object->entity];
    echo "Dossier base: " . $dir . "<br>";
    echo "Dossier avec ref: " . $dir . "/" . $object->ref . "<br>";
    
    echo "<h2>Test existence dossiers</h2>";
    
    // Test avec ref standard
    $standardPath = $dir . "/" . $object->ref;
    echo "Chemin standard: " . $standardPath . "<br>";
    echo "Existe: " . (file_exists($standardPath) ? "✅ Oui" : "❌ Non") . "<br>";
    
    // Test avec votre format personnalisé
    $customPath = $dir . "/25_04_003";
    echo "Chemin personnalisé: " . $customPath . "<br>";
    echo "Existe: " . (file_exists($customPath) ? "✅ Oui" : "❌ Non") . "<br>";
    
    echo "<h2>Contenu du dossier commande</h2>";
    if (file_exists($dir) && is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $fullPath = $dir . '/' . $file;
                if (is_dir($fullPath)) {
                    echo "📁 " . $file . "<br>";
                } else {
                    echo "📄 " . $file . "<br>";
                }
            }
        }
    }
    
    echo "<h2>Extrafields pour le nom de dossier</h2>";
    if (isset($object->array_options) && is_array($object->array_options)) {
        foreach ($object->array_options as $key => $value) {
            echo $key . ": " . $value . "<br>";
        }
    } else {
        echo "Aucun extrafield trouvé<br>";
    }
} else {
    echo "Erreur: Impossible de charger la commande $id";
}
