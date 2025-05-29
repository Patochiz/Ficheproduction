<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file        ficheproduction_buttons.php
 * \ingroup     ficheproduction
 * \brief       PDF buttons for production sheet
 */

/**
 * Generate PDF action buttons
 *
 * @param Commande $object Order object
 * @param User $user Current user
 * @param Translate $langs Language object
 * @param array $conf Configuration
 * @return void
 */
function generatePDFButtons($object, $user, $langs, $conf)
{
    // Check if PDF exists
    $pdfFile = $conf->commande->multidir_output[$object->entity]."/".$object->ref."/".$object->ref."-fiche-production.pdf";
    $pdfExists = file_exists($pdfFile);
    
    // Start action buttons section
    print '<div class="tabsAction">';
    
    // Save button (if user can edit)
    $userCanEdit = $user->rights->commande->creer ?? false;
    if ($userCanEdit) {
        print '<a class="butAction" href="javascript:saveColisage();" id="saveColisageBtn">💾 ' . $langs->trans("Save") . '</a>';
    }
    
    // Generate PDF button
    print '<a class="butAction" href="'.dol_buildpath('/ficheproduction/generate_pdf.php?id='.$object->id.'&action=builddoc', 1).'">📄 Générer PDF</a>';
    
    // View/Download PDF buttons if exists
    if ($pdfExists) {
        // View PDF button (opens in new tab)
        print '<a class="butAction" href="'.dol_buildpath('/ficheproduction/generate_pdf.php?id='.$object->id.'&action=builddoc', 1).'" target="_blank">👁️ Voir PDF</a>';
        
        // Download PDF button using document.php
        $pdfUrl = DOL_URL_ROOT.'/document.php?modulepart=commande&file='.$object->ref.'/'.$object->ref.'-fiche-production.pdf';
        print '<a class="butAction" href="'.$pdfUrl.'" target="_blank">⬇️ Télécharger PDF</a>';
        
        // Delete PDF button (for admin users only)
        if ($user->admin) {
            print '<a class="butActionDelete" href="'.dol_buildpath('/ficheproduction/generate_pdf.php?id='.$object->id.'&action=remove_file', 1).'" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ce fichier PDF ?\');">🗑️ Supprimer PDF</a>';
        }
    }
    
    // Print button (existing functionality)
    print '<a class="butAction" href="javascript:preparePrint();">' . $langs->trans("PrintButton") . '</a>';
    
    print '</div>';
    
    // Show PDF info if exists
    if ($pdfExists) {
        $filesize = filesize($pdfFile);
        $filedate = filemtime($pdfFile);
        print '<div class="info" style="margin-top: 10px; padding: 10px; background: #e8f5e8; border: 1px solid #4CAF50; border-radius: 4px;">';
        print '📄 <strong>Fiche de production PDF disponible</strong><br>';
        print 'Taille: '.dol_print_size($filesize).' - Généré le '.dol_print_date($filedate, 'dayhour');
        print '</div>';
    }
}
