<?php
/**
 * \file       fix_path_pdf.php
 * \ingroup    ficheproduction
 * \brief      Solution simple pour corriger le chemin PDF
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

echo "<h1>🔧 Correction du chemin PDF</h1>";

// Solution 1: Créer un lien symbolique
$baseDir = "/var/www/dolibarr/documents/commande";
$realDir = $baseDir . "/25_04_003";  // Votre dossier réel
$linkDir = $baseDir . "/CO2025-0011"; // Le dossier que cherche le PDF

if (file_exists($realDir) && !file_exists($linkDir)) {
    if (symlink($realDir, $linkDir)) {
        echo "✅ Lien symbolique créé: $linkDir → $realDir<br>";
    } else {
        echo "❌ Impossible de créer le lien symbolique<br>";
    }
} elseif (file_exists($linkDir)) {
    echo "✅ Le lien existe déjà: $linkDir<br>";
} else {
    echo "❌ Le dossier source n'existe pas: $realDir<br>";
}

// Solution 2: Copier le fichier PDF s'il existe déjà
$existingPdf = $realDir . "/25_04_003-fiche-production.pdf";
$targetPdf = $linkDir . "/CO2025-0011-fiche-production.pdf";

if (file_exists($existingPdf) && file_exists($linkDir)) {
    if (copy($existingPdf, $targetPdf)) {
        echo "✅ PDF copié vers le nouveau dossier<br>";
    }
}

echo "<h2>Test de génération PDF</h2>";
echo '<a href="generate_pdf.php?id=11&action=builddoc" target="_blank">🔗 Tester la génération PDF</a><br>';

echo "<h2>Vérification des dossiers</h2>";
echo "Dossier réel: " . (file_exists($realDir) ? "✅" : "❌") . " $realDir<br>";
echo "Dossier lien: " . (file_exists($linkDir) ? "✅" : "❌") . " $linkDir<br>";

if (file_exists($linkDir)) {
    echo "Contenu du dossier lien:<br>";
    $files = scandir($linkDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "  📄 $file<br>";
        }
    }
}
