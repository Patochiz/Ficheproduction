<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       generate_pdf.php
 * \ingroup    ficheproduction
 * \brief      Generate production sheet PDF
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

// Load required files
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

// Load FicheProduction PDF class
require_once dol_buildpath('/ficheproduction/class/ficheproductionpdf.class.php');

// Load translations
$langs->loadLangs(array('orders', 'products', 'companies'));
$langs->load('ficheproduction@ficheproduction');

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');

// Check permissions
if (!$user->rights->commande->lire) {
    accessforbidden();
}

// Initialize object
$object = new Commande($db);

// Load object
if ($id > 0 || !empty($ref)) {
    $result = $object->fetch($id, $ref);
    if ($result <= 0) {
        dol_print_error($db, $object->error);
        exit;
    }
} else {
    header('Location: '.dol_buildpath('/commande/list.php', 1));
    exit;
}

/*
 * Actions
 */

if ($action == 'builddoc') {
    // Generate PDF
    $hidedetails = GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : 0;
    $hidedesc = GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : 0;
    $hideref = GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : 0;
    
    $outputlangs = $langs;
    $newlang = '';
    
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
        $newlang = GETPOST('lang_id', 'aZ09');
    }
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
        $newlang = $object->thirdparty->default_lang;
    }
    if (!empty($newlang)) {
        $outputlangs = new Translate("", $conf);
        $outputlangs->setDefaultLang($newlang);
    }
    
    // Create PDF generator
    $pdfGenerator = new FicheProductionPDF($db);
    
    $result = $pdfGenerator->write_file($object, $outputlangs, '', $hidedetails, $hidedesc, $hideref);
    
    if ($result <= 0) {
        dol_print_error($db, $pdfGenerator->error);
        exit;
    } else {
        // Redirect to the generated PDF
        $filename = $object->ref."-fiche-production.pdf";
        $filepath = $conf->commande->multidir_output[$object->entity]."/".$object->ref."/".$filename;
        
        if (file_exists($filepath)) {
            // Set headers for PDF display
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="'.$filename.'"');
            header('Content-Length: '.filesize($filepath));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Output the PDF
            readfile($filepath);
            exit;
        } else {
            setEventMessages("Erreur lors de la génération du PDF", null, 'errors');
        }
    }
}

if ($action == 'remove_file') {
    // Remove PDF file
    $filename = $object->ref."-fiche-production.pdf";
    $filepath = $conf->commande->multidir_output[$object->entity]."/".$object->ref."/".$filename;
    
    if (file_exists($filepath)) {
        if (unlink($filepath)) {
            setEventMessages("Fichier supprimé avec succès", null, 'mesgs');
        } else {
            setEventMessages("Erreur lors de la suppression du fichier", null, 'errors');
        }
    }
    
    // Redirect back to ficheproduction page
    header('Location: '.dol_buildpath('/ficheproduction/ficheproduction.php?id='.$object->id, 1));
    exit;
}

// If no specific action, redirect back to main page
header('Location: '.dol_buildpath('/ficheproduction/ficheproduction.php?id='.$object->id, 1));
exit;
