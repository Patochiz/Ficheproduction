<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       class/ficheproductionpdf.class.php
 * \ingroup    ficheproduction
 * \brief      Class to generate production sheet PDF using TCPDF - Version complète finale
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load FicheProduction classes
require_once dol_buildpath('/ficheproduction/class/ficheproductionmanager.class.php');

/**
 * Class to generate production sheet PDF
 */
class FicheProductionPDF
{
    /**
     * @var DoliDB Database handler
     */
    public $db;
    
    /**
     * @var string Module name
     */
    public $name = 'ficheproduction';
    
    /**
     * @var string Module description
     */
    public $description = 'Production sheet PDF generator';
    
    /**
     * @var string Document type
     */
    public $type = 'pdf';
    
    /**
     * @var string Format (correction pour TCPDF)
     */
    public $format = 'A4';
    
    /**
     * @var string Error message
     */
    public $error = '';
    
    /**
     * @var array Supported formats
     */
    public $phpmin = array(5, 6);
    
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * Generate production sheet PDF - Version simplifiée pour les références Saphir
     *
     * @param Commande $object Order object
     * @param Translate $outputlangs Language object
     * @param string $srctemplatepath Template path
     * @param int $hidedetails Hide details
     * @param int $hidedesc Hide description
     * @param int $hideref Hide reference
     * @return int <0 if error, >0 if success
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $langs, $conf, $mysoc, $db, $hookmanager;
        
        try {
            if (!is_object($outputlangs)) $outputlangs = $langs;
            
            // Load translations
            $outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "orders"));
            $outputlangs->load('ficheproduction@ficheproduction');
            
            // Get object data
            if ($object->id <= 0) {
                $this->error = "Invalid object ID";
                return -1;
            }
            
            // Load object data
            $object->fetch_thirdparty();
            $object->fetch_lines();
            $object->fetch_optionals();
            
            // Get production data
            $manager = new FicheProductionManager($this->db);
            $productionData = $manager->loadColisageData($object->id);
            
            if (!$productionData['success']) {
                dol_syslog("No production data found for order ".$object->id, LOG_WARNING);
                // Continue anyway, will show empty production sheet
                $productionData = array('success' => true, 'colis' => array(), 'products' => array());
            }
            
            // Pour les références Saphir, utiliser directement $object->ref comme nom de dossier
            $dir = $conf->commande->multidir_output[$object->entity];
            $orderDir = $dir . "/" . $object->ref;  // Ex: /documents/commande/25_04_003
            $filename = $object->ref."-fiche-production.pdf";
            $file = $orderDir . "/" . $filename;
            
            // Create directory if it doesn't exist
            if (!file_exists($orderDir)) {
                if (dol_mkdir($orderDir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $orderDir);
                    return -1;
                }
            }
            
            // Verify directory is writable
            if (!is_writable($orderDir)) {
                $this->error = "Directory not writable: " . $orderDir;
                return -1;
            }
            
            // Initialize PDF
            $pdf = pdf_getInstance('', 'mm', 'A4');
            
            if (class_exists('TCPDF')) {
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
            }
            
            $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
            $pdf->SetSubject($outputlangs->transnoentities("ProductionSheet"));
            $pdf->SetCreator("Dolibarr ".DOL_VERSION);
            $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
            $pdf->SetKeywords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("ProductionSheet"));
            
            if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) $pdf->SetCompression(false);
            
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 10);
            
            // Add page
            $pdf->AddPage();
            
            // Generate content
            $this->_generateContent($pdf, $object, $productionData, $outputlangs);
            
            // Write file
            $pdf->Output($file, 'F');
            
            // Set permissions
            if (!empty($conf->global->MAIN_UMASK)) {
                @chmod($file, octdec($conf->global->MAIN_UMASK));
            }
            
            dol_syslog("FicheProduction PDF: File created successfully at " . $file, LOG_INFO);
            
            return 1;
            
        } catch (Exception $e) {
            $this->error = "Exception dans write_file: " . $e->getMessage();
            dol_syslog("FicheProductionPDF Error: " . $e->getMessage(), LOG_ERR);
            return -1;
        }
    }
    
    /**
     * Generate PDF content based on HTML mockup
     *
     * @param TCPDF $pdf PDF object
     * @param Commande $object Order object
     * @param array $productionData Production data
     * @param Translate $outputlangs Language object
     * @return void
     */
    protected function _generateContent($pdf, $object, $productionData, $outputlangs)
    {
        global $conf;
        
        try {
            // Set font
            $pdf->SetFont('helvetica', '', 8);
            
            // Current position
            $y = 15;
            
            // Header with title and status
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetXY(10, $y);
            $pdf->Cell(120, 8, 'FICHE DE PRODUCTION '.$object->ref, 0, 0, 'L');
            
            // Status (same as order status)
            $statusText = 'STATUT: ';
            switch ($object->statut) {
                case Commande::STATUS_DRAFT:
                    $statusText .= 'BROUILLON';
                    break;
                case Commande::STATUS_VALIDATED:
                case Commande::STATUS_SHIPMENTONPROCESS:
                    $statusText .= 'EN COURS';
                    break;
                case Commande::STATUS_CLOSED:
                    $statusText .= 'TERMINE';
                    break;
                case Commande::STATUS_CANCELED:
                    $statusText .= 'ANNULE';
                    break;
                default:
                    $statusText .= 'EN COURS';
            }
            
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(40, 167, 69); // Green color
            $pdf->Cell(70, 8, $statusText, 0, 1, 'R');
            $pdf->SetTextColor(0, 0, 0); // Reset to black
            
            $y += 15;
            
            // Draw separator line
            $pdf->Line(10, $y, 200, $y);
            $y += 8;
            
            // Order summary section (2 columns like mockup)
            $this->_generateOrderSummary($pdf, $object, $y, $outputlangs);
            $y += 50;
            
            // Main content section (Inventory 38% + Colis 62%)
            $this->_generateMainContent($pdf, $object, $productionData, $y, $outputlangs);
            $y += 120;
            
            // Totals section
            $this->_generateTotals($pdf, $productionData, $y, $outputlangs);
            $y += 25;
            
            // Controls and signatures section
            $this->_generateControls($pdf, $y, $outputlangs);
            
            // Footer
            $this->_generateFooter($pdf, $object, $outputlangs);
            
        } catch (Exception $e) {
            dol_syslog("Error in _generateContent: " . $e->getMessage(), LOG_ERR);
            throw $e;
        }
    }
    
    /**
     * Generate order summary section
     */
    protected function _generateOrderSummary($pdf, $object, &$y, $outputlangs)
    {
        // Left column - Delivery address (38% width)
        $leftWidth = 75;
        $rightWidth = 115;
        
        // Delivery address box
        $pdf->SetFillColor(248, 249, 250);
        $pdf->Rect(10, $y, $leftWidth, 35, 'F');
        $pdf->Rect(10, $y, $leftWidth, 35, 'D');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(12, $y + 2);
        $pdf->Cell($leftWidth - 4, 5, 'Adresse de livraison', 0, 1, 'L');
        
        // Get delivery contact
        $deliveryInfo = $this->_getDeliveryInfo($object);
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(12, $y + 8);
        $pdf->MultiCell($leftWidth - 4, 4, $deliveryInfo, 0, 'L');
        
        // Instructions box
        $pdf->SetFillColor(255, 243, 205);
        $pdf->Rect(10, $y + 37, $leftWidth, 15, 'F');
        $pdf->Rect(10, $y + 37, $leftWidth, 15, 'D');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(12, $y + 39);
        $pdf->Cell($leftWidth - 4, 4, 'Instructions', 0, 1, 'L');
        
        // Get instructions from thirdparty public note
        $instructions = !empty($object->thirdparty->note_public) ? 
            strip_tags($object->thirdparty->note_public) : 
            'Aucune instruction particuliere';
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(12, $y + 44);
        $pdf->MultiCell($leftWidth - 4, 3, $instructions, 0, 'L');
        
        // Right column - Order info table (62% width)
        $startX = 10 + $leftWidth + 5;
        
        // Info table
        $infoData = array(
            array('Date :', date('d/m/Y')),
            array('Client :', $object->thirdparty->name),
            array('Ref. Chantier :', $this->_getRefChantier($object)),
            array('Commentaires :', $this->_getCommentaires($object))
        );
        
        $currentY = $y;
        foreach ($infoData as $row) {
            // Draw row separator
            if ($currentY > $y) {
                $pdf->Line($startX, $currentY, $startX + $rightWidth, $currentY);
            }
            
            // Label cell
            $pdf->SetFillColor(248, 249, 250);
            $pdf->Rect($startX, $currentY, 40, 8, 'F');
            $pdf->Rect($startX, $currentY, 40, 8, 'D');
            
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY($startX + 2, $currentY + 2);
            $pdf->Cell(36, 4, $row[0], 0, 0, 'L');
            
            // Value cell
            $pdf->Rect($startX + 40, $currentY, $rightWidth - 40, 8, 'D');
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetXY($startX + 42, $currentY + 2);
            $pdf->Cell($rightWidth - 44, 4, $row[1], 0, 0, 'L');
            
            $currentY += 8;
        }
        
        // Final border
        $pdf->Line($startX, $currentY, $startX + $rightWidth, $currentY);
    }
    
    /**
     * Generate main content (inventory + colis)
     */
    protected function _generateMainContent($pdf, $object, $productionData, &$y, $outputlangs)
    {
        $leftWidth = 75; // 38%
        $rightWidth = 115; // 62%
        
        // Left column - Inventory
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(10, $y);
        $pdf->Cell($leftWidth, 6, 'INVENTAIRE PRODUITS', 0, 1, 'L');
        
        $currentY = $y + 8;
        
        // Generate product groups from order lines
        $productGroups = $this->_getProductGroups($object);
        
        foreach ($productGroups as $group) {
            // Group header
            $pdf->SetFillColor(233, 236, 239);
            $pdf->Rect(10, $currentY, $leftWidth, 6, 'F');
            $pdf->Rect(10, $currentY, $leftWidth, 6, 'D');
            
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY(12, $currentY + 1);
            $pdf->Cell($leftWidth - 4, 4, $group['header'], 0, 1, 'L');
            $currentY += 6;
            
            // Group details
            $pdf->SetFillColor(255, 255, 255);
            $detailHeight = count($group['details']) * 3 + 2;
            $pdf->Rect(10, $currentY, $leftWidth, $detailHeight, 'F');
            $pdf->Rect(10, $currentY, $leftWidth, $detailHeight, 'D');
            
            $pdf->SetFont('helvetica', '', 7);
            $detailY = $currentY + 1;
            foreach ($group['details'] as $detail) {
                $pdf->SetXY(15, $detailY);
                $pdf->Cell($leftWidth - 10, 3, '• '.$detail, 0, 1, 'L');
                $detailY += 3;
            }
            $currentY += $detailHeight + 2;
        }
        
        // Right column - Colis list
        $startX = 10 + $leftWidth + 5;
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY($startX, $y);
        $pdf->Cell($rightWidth, 6, 'LISTE DES COLIS PREPARES', 0, 1, 'L');
        
        $currentY = $y + 8;
        
        // Generate colis from production data
        if (!empty($productionData['colis'])) {
            foreach ($productionData['colis'] as $index => $colis) {
                // Colis header
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetXY($startX, $currentY);
                $colisTitle = ($index + 1).' colis n°'.($colis['number'] ?? ($index + 1)).' ('.($colis['weight'] ?? '0').' Kg/colis)';
                $pdf->Cell($rightWidth, 4, $colisTitle, 0, 1, 'L');
                $currentY += 4;
                
                // Colis content
                $pdf->SetFont('helvetica', '', 7);
                if (!empty($colis['products'])) {
                    foreach ($colis['products'] as $product) {
                        $pdf->SetXY($startX + 5, $currentY);
                        $productLine = '• '.$product['name'].' - '.$product['color'].' '.$product['length'].'x'.$product['width'].'mm ('.$product['quantity'].' pcs)';
                        $pdf->Cell($rightWidth - 5, 3, $productLine, 0, 1, 'L');
                        $currentY += 3;
                    }
                }
                $currentY += 2;
            }
        } else {
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetXY($startX, $currentY);
            $pdf->Cell($rightWidth, 6, 'Aucun colis prepare', 0, 1, 'L');
        }
    }
    
    /**
     * Generate totals section
     */
    protected function _generateTotals($pdf, $productionData, &$y, $outputlangs)
    {
        // Calculate totals
        $totalColis = count($productionData['colis'] ?? array());
        $totalWeight = 0;
        
        if (!empty($productionData['colis'])) {
            foreach ($productionData['colis'] as $colis) {
                $totalWeight += floatval($colis['weight'] ?? 0);
            }
        }
        
        // Totals box
        $pdf->SetFillColor(248, 249, 250);
        $pdf->Rect(10, $y, 190, 15, 'F');
        $pdf->SetDrawColor(40, 167, 69);
        $pdf->SetLineWidth(1);
        $pdf->Rect(10, $y, 190, 15, 'D');
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(21, 87, 36);
        
        $pdf->SetXY(50, $y + 5);
        $pdf->Cell(90, 5, 'TOTAL COLIS PREPARES : '.$totalColis.' colis', 0, 0, 'C');
        
        $pdf->SetXY(50, $y + 10);
        $pdf->Cell(90, 5, 'POIDS TOTAL : '.number_format($totalWeight, 1).' kg', 0, 0, 'C');
        
        $pdf->SetTextColor(0, 0, 0);
    }
    
    /**
     * Generate controls section
     */
    protected function _generateControls($pdf, &$y, $outputlangs)
    {
        // Section title
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(10, $y);
        $pdf->Cell(190, 6, 'CONTROLES DE PRODUCTION ET SIGNATURES', 0, 1, 'C');
        
        // Draw separator
        $pdf->Line(10, $y + 6, 200, $y + 6);
        $y += 10;
        
        // Three columns layout
        $col1Width = 63;
        $col2Width = 63;
        $col3Width = 64;
        
        // Column 1 - Final packaging
        $pdf->Rect(10, $y, $col1Width, 40, 'D');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(10, $y + 2);
        $pdf->Cell($col1Width, 4, 'COLISAGE FINAL', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 7);
        $checkItems = array(
            '_____ Palettes = _____ Colis',
            '_____ Fagots = _____ Colis',
            '_____ Colis vrac'
        );
        
        $checkY = $y + 8;
        foreach ($checkItems as $item) {
            $pdf->Rect(12, $checkY, 3, 3, 'D'); // Checkbox
            $pdf->SetXY(17, $checkY);
            $pdf->Cell($col1Width - 9, 3, $item, 0, 1, 'L');
            $checkY += 5;
        }
        
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(10, $y + 30);
        $pdf->Cell($col1Width, 4, 'TOTAL: _____ COLIS', 0, 1, 'C');
        
        // Column 2 - Quality controls
        $startX2 = 10 + $col1Width;
        $pdf->Rect($startX2, $y, $col2Width, 40, 'D');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY($startX2, $y + 2);
        $pdf->Cell($col2Width, 4, 'CONTROLES QUALITE', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 7);
        $qualityItems = array(
            'Dimensions conformes',
            'Couleurs conformes',
            'Quantites verifiees',
            'Etiquetage complet',
            'Emballage conforme'
        );
        
        $checkY = $y + 8;
        foreach ($qualityItems as $item) {
            $pdf->Rect($startX2 + 2, $checkY, 3, 3, 'D'); // Checkbox
            $pdf->SetXY($startX2 + 7, $checkY);
            $pdf->Cell($col2Width - 9, 3, $item, 0, 1, 'L');
            $checkY += 5;
        }
        
        // Column 3 - Signatures
        $startX3 = 10 + $col1Width + $col2Width;
        $pdf->Rect($startX3, $y, $col3Width, 40, 'D');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY($startX3, $y + 2);
        $pdf->Cell($col3Width, 4, 'RESPONSABLES', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 7);
        $signatures = array(
            'Production:',
            'Controle:',
            'Expedition:'
        );
        
        $sigY = $y + 8;
        foreach ($signatures as $sig) {
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->SetXY($startX3 + 2, $sigY);
            $pdf->Cell($col3Width - 4, 3, $sig, 0, 1, 'L');
            
            // Signature line
            $pdf->Line($startX3 + 2, $sigY + 8, $startX3 + $col3Width - 2, $sigY + 8);
            $sigY += 12;
        }
        
        // Bobines ID
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY($startX3 + 2, $y + 35);
        $pdf->Cell($col3Width - 4, 3, 'Bobines ID: __________', 0, 1, 'L');
    }
    
    /**
     * Generate footer
     */
    protected function _generateFooter($pdf, $object, $outputlangs)
    {
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(102, 102, 102);
        
        // Calculate total products
        $totalProducts = 0;
        if (!empty($object->lines)) {
            foreach ($object->lines as $line) {
                if ($line->fk_product > 0) {
                    $quantity = isset($line->array_options['options_nombre']) && !empty($line->array_options['options_nombre']) 
                        ? intval($line->array_options['options_nombre'])
                        : intval($line->qty);
                    $totalProducts += $quantity;
                }
            }
        }
        
        $footerText = 'Fiche generee le '.date('d/m/Y').' a '.date('H:i').' | Total: '.$totalProducts.' pcs commandees | Document confidentiel';
        
        $pdf->SetXY(10, 280);
        $pdf->Cell(190, 4, $footerText, 0, 1, 'C');
        
        // Draw line above footer
        $pdf->Line(10, 278, 200, 278);
    }
    
    /**
     * Get delivery information
     */
    protected function _getDeliveryInfo($object)
    {
        global $langs;
        
        $deliveryInfo = '';
        
        // Get delivery contacts
        $contacts = $object->liste_contact(-1, 'external', 0, 'SHIPPING');
        if (is_array($contacts) && count($contacts) > 0) {
            foreach ($contacts as $contact) {
                $contactstatic = new Contact($this->db);
                if ($contactstatic->fetch($contact['id']) > 0) {
                    $deliveryInfo = $contactstatic->getFullName($langs)."\n";
                    $deliveryInfo .= $contactstatic->address."\n";
                    $deliveryInfo .= $contactstatic->zip." ".$contactstatic->town."\n";
                    
                    if (!empty($contactstatic->phone_pro)) {
                        $deliveryInfo .= "Tel: ".$contactstatic->phone_pro;
                    }
                    if (!empty($contactstatic->phone_mobile)) {
                        $deliveryInfo .= (!empty($contactstatic->phone_pro) ? " / " : "Tel: ").$contactstatic->phone_mobile;
                    }
                    if (!empty($contactstatic->phone_pro) || !empty($contactstatic->phone_mobile)) {
                        $deliveryInfo .= "\n";
                    }
                    
                    if (!empty($contactstatic->email)) {
                        $deliveryInfo .= "Email: ".$contactstatic->email;
                    }
                }
                break;
            }
        }
        
        // Fallback to thirdparty address
        if (empty($deliveryInfo)) {
            $deliveryInfo = $object->thirdparty->name."\n";
            $deliveryInfo .= $object->thirdparty->address."\n";
            $deliveryInfo .= $object->thirdparty->zip." ".$object->thirdparty->town;
            if (!empty($object->thirdparty->phone)) {
                $deliveryInfo .= "\nTel: ".$object->thirdparty->phone;
            }
            if (!empty($object->thirdparty->email)) {
                $deliveryInfo .= "\nEmail: ".$object->thirdparty->email;
            }
        }
        
        return $deliveryInfo;
    }
    
    /**
     * Get reference chantier from extrafields
     */
    protected function _getRefChantier($object)
    {
        if (!empty($object->array_options['options_ref_chantierfp'])) {
            return $object->array_options['options_ref_chantierfp'];
        } elseif (!empty($object->array_options['options_ref_chantier'])) {
            return $object->array_options['options_ref_chantier'];
        }
        return 'Non defini';
    }
    
    /**
     * Get commentaires from extrafields
     */
    protected function _getCommentaires($object)
    {
        if (!empty($object->array_options['options_commentaires_fp'])) {
            return strip_tags($object->array_options['options_commentaires_fp']);
        }
        return 'Aucun commentaire';
    }
    
    /**
     * Get product groups from order lines
     */
    protected function _getProductGroups($object)
    {
        $groups = array();
        
        if (!empty($object->lines)) {
            foreach ($object->lines as $line) {
                if ($line->fk_product > 0) {
                    $product = new Product($this->db);
                    if ($product->fetch($line->fk_product) > 0 && $product->type == 0) {
                        // Get quantity from extrafield nombre
                        $quantity = isset($line->array_options['options_nombre']) && !empty($line->array_options['options_nombre']) 
                            ? intval($line->array_options['options_nombre'])
                            : intval($line->qty);
                        
                        if ($quantity > 0) {
 
                            // Get dimensions and color
                            $length = $this->_getExtraFieldValue($line, array('length', 'longueur', 'long'), 1000);
                            $width = $this->_getExtraFieldValue($line, array('width', 'largeur', 'larg'), 100);
                            $color = $this->_getExtraFieldValue($line, array('color', 'couleur'), 'Standard');
                            
                            $groupKey = $product->label.' - '.$color;
                            
                            if (!isset($groups[$groupKey])) {
                                $groups[$groupKey] = array(
                                    'header' => $groupKey.' ('.$quantity.' pcs commandees)',
                                    'details' => array()
                                );
                            }
                            
                            $groups[$groupKey]['details'][] = $length.'mm x '.$width.'mm ('.$quantity.' pcs)';
                        }
                    }
                }
            }
        }
        
        return array_values($groups);
    }
    
    /**
     * Get extrafield value with fallback options
     */
    protected function _getExtraFieldValue($line, $fieldNames, $defaultValue)
    {
        if (isset($line->array_options) && is_array($line->array_options)) {
            foreach ($fieldNames as $fieldName) {
                $optionKey = 'options_'.$fieldName;
                if (isset($line->array_options[$optionKey]) && !empty($line->array_options[$optionKey])) {
                    return $line->array_options[$optionKey];
                }
            }
        }
        return $defaultValue;
    }
}
