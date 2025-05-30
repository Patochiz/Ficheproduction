    /**
     * Get the correct directory reference for file storage
     * Handles custom numbering models like Saphir
     */
    protected function _getFileReference($object)
    {
        // For custom numbering, Dolibarr might use different refs for display vs file storage
        
        // Method 1: Try to get the file reference from document.php logic
        if (method_exists($object, 'getDocumentsDir')) {
            return basename($object->getDocumentsDir());
        }
        
        // Method 2: Check if there's a specific extrafield for file reference
        if (!empty($object->array_options['options_ref_file'])) {
            return $object->array_options['options_ref_file'];
        }
        
        // Method 3: Use the internal ref (might work for file paths)
        if (!empty($object->ref_int)) {
            return $object->ref_int;
        }
        
        // Method 4: Fallback to standard ref
        return $object->ref;
    }
    
    /**
     * Get the actual directory path for the order
     */
    protected function _getOrderDirectory($object, $conf)
    {
        $baseDir = $conf->commande->multidir_output[$object->entity];
        $fileRef = $this->_getFileReference($object);
        
        $standardPath = $baseDir . "/" . $fileRef;
        
        // If the standard path doesn't exist, try to find the actual directory
        if (!file_exists($standardPath)) {
            // Scan the base directory to find a matching folder
            if (file_exists($baseDir) && is_dir($baseDir)) {
                $dirs = scandir($baseDir);
                foreach ($dirs as $dir) {
                    if ($dir != '.' && $dir != '..' && is_dir($baseDir . '/' . $dir)) {
                        // Check if this directory contains files for our order
                        $testPath = $baseDir . '/' . $dir;
                        
                        // Look for any PDF files that might indicate this is our order directory
                        $files = glob($testPath . '/*.pdf');
                        if (!empty($files)) {
                            foreach ($files as $file) {
                                // If filename contains our display ref, this might be it
                                if (strpos(basename($file), str_replace('/', '_', $object->ref)) !== false) {
                                    return $testPath;
                                }
                            }
                        }
                        
                        // Alternative: if the directory name matches our display ref pattern
                        if (preg_match('/^\d{2}_\d{2}_\d{3}$/', $dir)) {
                            // This looks like a Saphir-generated directory
                            // We'll use this as our best guess
                            $possiblePath = $testPath;
                        }
                    }
                }
                
                // If we found a possible Saphir directory, use it
                if (isset($possiblePath)) {
                    return $possiblePath;
                }
            }
        }
        
        return $standardPath;
    }
    
    /**
     * Generate production sheet PDF - Updated version with path handling
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
            
            // Get the correct directory path (handles custom numbering)
            $orderDir = $this->_getOrderDirectory($object, $conf);
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
            
            // Create PDF
            // Initialize PDF avec gestion d'erreur
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