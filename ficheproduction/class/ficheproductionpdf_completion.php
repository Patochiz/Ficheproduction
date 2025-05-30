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
