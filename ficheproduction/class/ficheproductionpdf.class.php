                        
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
