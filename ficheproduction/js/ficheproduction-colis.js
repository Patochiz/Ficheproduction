/**
 * FicheProduction v2.0 - Module Colis (Version Corrigée - Boutons Fonctionnels)
 * Gestion complète des colis avec enregistrement amélioré
 */

(function() {
    'use strict';

    // ============================================================================
    // GESTION DES COLIS
    // ============================================================================

    /**
     * Ajouter un nouveau colis (FONCTION CRITIQUE)
     */
    function addNewColis() {
        debugLog('Ajout nouveau colis');
        const colis = FicheProduction.data.colis();
        const newId = Math.max(...colis.map(c => c.id), 0) + 1;
        const newNumber = Math.max(...colis.map(c => c.number), 0) + 1;
        
        const newColis = {
            id: newId,
            number: newNumber,
            products: [],
            totalWeight: 0,
            maxWeight: 25,
            status: 'ok',
            multiple: 1,
            isLibre: false
        };

        FicheProduction.data.addColis(newColis);
        renderColisOverview();
        selectColis(newColis);
        updateSummaryTotals(); // Mettre à jour les totaux
    }

    /**
     * Supprimer un colis
     * @param {number} colisId - ID du colis à supprimer
     */
    async function deleteColis(colisId) {
        debugLog(`Tentative suppression colis ID: ${colisId}`);
        
        const confirmed = await FicheProduction.ui.showConfirm('Êtes-vous sûr de vouloir supprimer ce colis ?');
        if (!confirmed) {
            debugLog('Suppression annulée par utilisateur');
            return;
        }

        const colis = FicheProduction.data.colis();
        const products = FicheProduction.data.products();
        const coliData = colis.find(c => c.id === colisId);
        
        if (!coliData) {
            debugLog('ERREUR: Colis non trouvé');
            await FicheProduction.ui.showConfirm('Erreur: Colis non trouvé');
            return;
        }
        
        debugLog(`Suppression colis: ${JSON.stringify(coliData)}`);
        
        // Remettre tous les produits dans l'inventaire (sauf les produits libres)
        coliData.products.forEach(p => {
            const product = products.find(prod => prod.id === p.productId);
            if (product && !product.isLibre) {
                const quantityToRestore = p.quantity * coliData.multiple;
                product.used -= quantityToRestore;
                debugLog(`Remise en stock: ${product.ref} +${quantityToRestore}`);
            }
        });

        // Supprimer les produits libres de la liste globale
        if (coliData.isLibre) {
            coliData.products.forEach(p => {
                const productIndex = products.findIndex(prod => prod.id === p.productId && prod.isLibre);
                if (productIndex > -1) {
                    products.splice(productIndex, 1);
                    debugLog(`Produit libre supprimé: ${p.productId}`);
                }
            });
        }

        // Supprimer le colis
        FicheProduction.data.removeColis(colisId);
        
        // Déselectionner si c'était le colis sélectionné
        const selectedColis = FicheProduction.data.selectedColis();
        if (selectedColis && selectedColis.id === colisId) {
            FicheProduction.data.setSelectedColis(null);
            debugLog('Colis désélectionné');
        }

        // Re-render
        if (FicheProduction.inventory.renderInventory) {
            FicheProduction.inventory.renderInventory();
        }
        renderColisOverview();
        renderColisDetail();
        updateSummaryTotals(); // Mettre à jour les totaux
        
        debugLog('Interface mise à jour après suppression');
    }

    /**
     * Sélectionner un colis
     * @param {Object} coliData - Données du colis
     */
    function selectColis(coliData) {
        debugLog(`Sélection colis ${coliData.id}`);
        FicheProduction.data.setSelectedColis(coliData);
        renderColisOverview();
        renderColisDetail();
    }

    /**
     * Ajouter un produit à un colis
     * @param {number} colisId - ID du colis
     * @param {number} productId - ID du produit
     * @param {number} quantity - Quantité à ajouter
     */
    function addProductToColis(colisId, productId, quantity) {
        debugLog(`🔧 Ajout produit ${productId} (qté: ${quantity}) au colis ${colisId}`);
        
        const colis = FicheProduction.data.colis();
        const products = FicheProduction.data.products();
        const coliData = colis.find(c => c.id === colisId);
        const product = products.find(p => p.id === productId);
        
        if (!coliData || !product) {
            debugLog('ERREUR: Colis ou produit non trouvé');
            return;
        }

        // Ne pas permettre d'ajouter des produits normaux aux colis libres
        if (coliData.isLibre) {
            alert('Impossible d\'ajouter des produits de la commande à un colis libre.');
            return;
        }

        // Vérifier la disponibilité
        const available = product.total - product.used;
        if (available < quantity) {
            alert(`Quantité insuffisante ! Disponible: ${available}, Demandé: ${quantity}`);
            return;
        }

        // Vérifier si le produit est déjà dans le colis
        const existingProduct = coliData.products.find(p => p.productId === productId);
        
        if (existingProduct) {
            existingProduct.quantity += quantity;
            existingProduct.weight = existingProduct.quantity * product.weight;
        } else {
            coliData.products.push({
                productId: productId,
                quantity: quantity,
                weight: quantity * product.weight
            });
        }

        // Recalculer le poids total
        coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

        // Mettre à jour les quantités utilisées
        product.used += quantity * coliData.multiple;

        // Re-render
        if (FicheProduction.inventory.renderInventory) {
            FicheProduction.inventory.renderInventory();
        }
        renderColisOverview();
        
        const selectedColis = FicheProduction.data.selectedColis();
        if (selectedColis && selectedColis.id === colisId) {
            renderColisDetail();
        }
        updateSummaryTotals();
    }

    /**
     * Supprimer un produit d'un colis
     * @param {number} colisId - ID du colis
     * @param {number} productId - ID du produit
     */
    function removeProductFromColis(colisId, productId) {
        const colis = FicheProduction.data.colis();
        const products = FicheProduction.data.products();
        const coliData = colis.find(c => c.id === colisId);
        const productInColis = coliData ? coliData.products.find(p => p.productId === productId) : null;
        
        if (!coliData || !productInColis) {
            return;
        }

        // Remettre les quantités dans l'inventaire
        const product = products.find(p => p.id === productId);
        if (product && !product.isLibre) {
            product.used -= productInColis.quantity * coliData.multiple;
        }

        // Supprimer le produit du colis
        const productIndex = coliData.products.findIndex(p => p.productId === productId);
        if (productIndex > -1) {
            coliData.products.splice(productIndex, 1);
        }
        
        // Recalculer le poids total
        coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

        // Re-render
        if (FicheProduction.inventory.renderInventory) {
            FicheProduction.inventory.renderInventory();
        }
        renderColisOverview();
        renderColisDetail();
        updateSummaryTotals();
    }

    /**
     * Mettre à jour la quantité d'un produit dans un colis
     * @param {number} colisId - ID du colis
     * @param {number} productId - ID du produit
     * @param {number} newQuantity - Nouvelle quantité
     */
    function updateProductQuantity(colisId, productId, newQuantity) {
        const colis = FicheProduction.data.colis();
        const products = FicheProduction.data.products();
        const coliData = colis.find(c => c.id === colisId);
        const productInColis = coliData ? coliData.products.find(p => p.productId === productId) : null;
        const product = products.find(p => p.id === productId);
        
        if (!productInColis || !product || !coliData) {
            return;
        }

        const oldQuantity = productInColis.quantity;
        const quantityDiff = parseInt(newQuantity) - oldQuantity;

        // Pour les produits libres, pas de vérification de stock
        if (product.isLibre) {
            productInColis.quantity = parseInt(newQuantity);
            productInColis.weight = productInColis.quantity * product.weight;
            
            coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);
            
            if (FicheProduction.inventory.renderInventory) {
                FicheProduction.inventory.renderInventory();
            }
            renderColisOverview();
            renderColisDetail();
            updateSummaryTotals();
            return;
        }

        // Vérifier la disponibilité pour les produits normaux
        const totalQuantityNeeded = quantityDiff * coliData.multiple;
        const available = product.total - product.used;
        
        if (totalQuantityNeeded > available) {
            alert(`Quantité insuffisante ! Disponible: ${available}, Besoin: ${totalQuantityNeeded}`);
            const input = document.querySelector(`input[data-product-id="${productId}"]`);
            if (input) input.value = oldQuantity;
            return;
        }

        // Mettre à jour les quantités
        productInColis.quantity = parseInt(newQuantity);
        productInColis.weight = productInColis.quantity * product.weight;
        product.used += totalQuantityNeeded;

        // Recalculer le poids total
        coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

        // Re-render
        if (FicheProduction.inventory.renderInventory) {
            FicheProduction.inventory.renderInventory();
        }
        renderColisOverview();
        renderColisDetail();
        updateSummaryTotals();
    }

    /**
     * Afficher la boîte de dialogue pour dupliquer un colis
     * @param {number} colisId - ID du colis
     */
    async function showDuplicateDialog(colisId) {
        const colis = FicheProduction.data.colis();
        const coliData = colis.find(c => c.id === colisId);
        
        if (!coliData) {
            await FicheProduction.ui.showConfirm('Erreur: Colis non trouvé');
            return;
        }

        const currentMultiple = coliData.multiple || 1;
        const message = `Combien de fois créer ce colis identique ?\n\nActuellement: ${currentMultiple} colis`;
        const newMultiple = await FicheProduction.ui.showPrompt(message, currentMultiple.toString());
        
        if (newMultiple !== null && !isNaN(newMultiple) && parseInt(newMultiple) > 0) {
            updateColisMultiple(colisId, parseInt(newMultiple));
        } else if (newMultiple !== null) {
            await FicheProduction.ui.showConfirm('Veuillez saisir un nombre entier positif');
        }
    }

    /**
     * Mettre à jour le nombre de multiples pour un colis
     * @param {number} colisId - ID du colis
     * @param {number} multiple - Nouveau nombre de multiples
     */
    async function updateColisMultiple(colisId, multiple) {
        const colis = FicheProduction.data.colis();
        const products = FicheProduction.data.products();
        const coliData = colis.find(c => c.id === colisId);
        
        if (!coliData) {
            return;
        }

        const oldMultiple = coliData.multiple;
        const newMultiple = parseInt(multiple);
        
        if (isNaN(newMultiple) || newMultiple < 1) {
            await FicheProduction.ui.showConfirm('Le nombre de colis doit être un entier positif');
            return;
        }

        // Calculer la différence pour ajuster les quantités utilisées
        const multipleDiff = newMultiple - oldMultiple;
        
        // Mettre à jour les quantités utilisées pour chaque produit (sauf libres)
        for (const p of coliData.products) {
            const product = products.find(prod => prod.id === p.productId);
            if (product && !product.isLibre) {
                product.used += p.quantity * multipleDiff;
                
                // Vérifier qu'on ne dépasse pas le total disponible
                if (product.used > product.total) {
                    await FicheProduction.ui.showConfirm(`Attention: ${product.ref} - Quantité dépassée! Utilisé: ${product.used}, Total: ${product.total}`);
                    // Revenir à l'ancienne valeur
                    product.used -= p.quantity * multipleDiff;
                    return;
                }
            }
        }

        coliData.multiple = newMultiple;
        
        if (FicheProduction.inventory.renderInventory) {
            FicheProduction.inventory.renderInventory();
        }
        renderColisOverview();
        
        const selectedColis = FicheProduction.data.selectedColis();
        if (selectedColis && selectedColis.id === colisId) {
            renderColisDetail();
        }
        updateSummaryTotals();
    }

    /**
     * Mettre à jour les totaux dans le tableau récapitulatif
     */
    function updateSummaryTotals() {
        const colis = FicheProduction.data.colis();
        
        // Calculer le nombre total de colis
        let totalPackages = 0;
        let totalWeight = 0;
        
        colis.forEach(c => {
            totalPackages += c.multiple;
            totalWeight += c.totalWeight * c.multiple;
        });
        
        // Mettre à jour l'affichage
        const totalPackagesElement = document.getElementById('total-packages');
        const totalWeightElement = document.getElementById('total-weight');
        
        if (totalPackagesElement) {
            totalPackagesElement.textContent = totalPackages;
        }
        
        if (totalWeightElement) {
            totalWeightElement.textContent = totalWeight.toFixed(1);
        }
        
        debugLog(`Totaux mis à jour: ${totalPackages} colis, ${totalWeight.toFixed(1)} kg`);
    }

    /**
     * Rendre la vue d'ensemble des colis
     */
    function renderColisOverview() {
        const tbody = document.getElementById('colisTableBody');
        if (!tbody) {
            debugLog('❌ Élément colisTableBody non trouvé');
            return;
        }
        
        tbody.innerHTML = '';

        const colis = FicheProduction.data.colis();
        const products = FicheProduction.data.products();
        const selectedColis = FicheProduction.data.selectedColis();

        if (colis.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Aucun colis créé. Cliquez sur "Nouveau Colis" pour commencer.</td></tr>';
            return;
        }

        colis.forEach(c => {
            const weightPercentage = (c.totalWeight / c.maxWeight) * 100;
            let statusIcon = '✅';
            let statusClass = '';
            if (weightPercentage > 90) {
                statusIcon = '⚠️';
                statusClass = 'warning';
            } else if (weightPercentage > 100) {
                statusIcon = '❌';
                statusClass = 'error';
            }

            // Ligne d'en-tête pour le colis
            const headerRow = document.createElement('tr');
            headerRow.className = 'colis-group-header';
            if (c.isLibre) {
                headerRow.classList.add('colis-libre');
            }
            headerRow.dataset.colisId = c.id;
            if (selectedColis && selectedColis.id === c.id) {
                headerRow.classList.add('selected');
            }

            const totalColis = c.multiple;
            const leftText = totalColis > 1 ? `${totalColis} colis` : '1 colis';
            const colisType = c.isLibre ? 'LIBRE' : c.number;
            const rightText = `Colis ${colisType} (${c.products.length} produit${c.products.length > 1 ? 's' : ''}) - ${c.totalWeight.toFixed(1)} Kg ${statusIcon}`;

            headerRow.innerHTML = `
                <td colspan="6">
                    <div class="colis-header-content">
                        <span class="colis-header-left">${c.isLibre ? '📦' : '📦'} ${leftText}</span>
                        <span class="colis-header-right">${rightText}</span>
                    </div>
                </td>
            `;

            // Event listener pour sélectionner le colis
            headerRow.addEventListener('click', () => {
                selectColis(c);
            });

            // Setup drop zone pour l'en-tête du colis (seulement pour colis normaux)
            if (!c.isLibre && FicheProduction.dragdrop.setupDropZone) {
                FicheProduction.dragdrop.setupDropZone(headerRow, c.id);
            }
            tbody.appendChild(headerRow);

            // Lignes pour chaque produit dans le colis
            if (c.products.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.className = 'colis-group-item';
                if (c.isLibre) {
                    emptyRow.classList.add('colis-libre');
                }
                emptyRow.dataset.colisId = c.id;
                emptyRow.innerHTML = `
                    <td></td>
                    <td colspan="5" style="font-style: italic; color: #999; padding: 10px;">
                        Colis vide - ${c.isLibre ? 'Colis libre sans éléments' : 'Glissez des produits ici'}
                    </td>
                `;
                
                if (!c.isLibre && FicheProduction.dragdrop.setupDropZone) {
                    FicheProduction.dragdrop.setupDropZone(emptyRow, c.id);
                }
                tbody.appendChild(emptyRow);
            } else {
                c.products.forEach((productInColis, index) => {
                    const product = products.find(p => p.id === productInColis.productId);
                    if (!product) return;

                    const productRow = document.createElement('tr');
                    productRow.className = 'colis-group-item';
                    if (c.isLibre) {
                        productRow.classList.add('colis-libre');
                    }
                    productRow.dataset.colisId = c.id;
                    productRow.dataset.productId = product.id;

                    const dimensionsDisplay = product.isLibre ? 
                        `Poids unit.: ${product.weight}kg` : 
                        `${product.length}×${product.width}`;

                    const colorDisplay = product.isLibre ? 
                        'LIBRE' : 
                        product.color;

                    productRow.innerHTML = `
                        <td></td>
                        <td>
                            <div class="product-label">
                                <span>${product.name}</span>
                                <span class="product-color-badge ${product.isLibre ? 'libre-badge' : ''}">${colorDisplay}</span>
                            </div>
                            ${product.ref_ligne ? `<div style="font-size: 10px; color: #888; font-style: italic;">Réf: ${product.ref_ligne}</div>` : ''}
                        </td>
                        <td style="font-weight: bold; text-align: right; vertical-align: top;">
                            ${productInColis.quantity}
                            ${c.multiple > 1 ? `<div style="font-size: 10px; color: #666;">×${c.multiple} = ${productInColis.quantity * c.multiple}</div>` : ''}
                        </td>
                        <td style="font-weight: bold; text-align: left; vertical-align: top;">
                            ${dimensionsDisplay}
                            <div style="font-size: 10px; color: #666;">${productInColis.weight.toFixed(1)}kg</div>
                        </td>
                        <td class="${statusClass}" style="text-align: center;">
                            ${statusIcon}
                        </td>
                        <td>
                            <button class="btn-small btn-edit" title="Modifier quantité" 
                                    data-colis-id="${c.id}" data-product-id="${product.id}">📝</button>
                            <button class="btn-small btn-delete" title="Supprimer" 
                                    data-colis-id="${c.id}" data-product-id="${product.id}">🗑️</button>
                            ${index === 0 ? `<button class="btn-small btn-duplicate" title="Dupliquer colis" 
                                            data-colis-id="${c.id}">×${c.multiple}</button>` : ''}
                        </td>
                    `;

                    // Event listeners pour les boutons
                    const editBtn = productRow.querySelector('.btn-edit');
                    const deleteBtn = productRow.querySelector('.btn-delete');
                    const duplicateBtn = productRow.querySelector('.btn-duplicate');

                    if (editBtn) {
                        editBtn.addEventListener('click', async (e) => {
                            e.stopPropagation();
                            const stockInfo = product.isLibre ? '' : `\n(Stock disponible: ${product.total - product.used})`;
                            const newQuantity = await FicheProduction.ui.showPrompt(
                                `Nouvelle quantité pour ${product.name} :${stockInfo}`,
                                productInColis.quantity.toString()
                            );
                            if (newQuantity !== null && !isNaN(newQuantity) && parseInt(newQuantity) > 0) {
                                updateProductQuantity(c.id, product.id, parseInt(newQuantity));
                            }
                        });
                    }

                    if (deleteBtn) {
                        deleteBtn.addEventListener('click', async (e) => {
                            e.stopPropagation();
                            const confirmed = await FicheProduction.ui.showConfirm(
                                `Supprimer ${product.name} du colis ${c.isLibre ? 'libre' : c.number} ?`
                            );
                            if (confirmed) {
                                removeProductFromColis(c.id, product.id);
                            }
                        });
                    }

                    if (duplicateBtn) {
                        duplicateBtn.addEventListener('click', async (e) => {
                            e.stopPropagation();
                            await showDuplicateDialog(c.id);
                        });
                    }

                    if (!c.isLibre && FicheProduction.dragdrop.setupDropZone) {
                        FicheProduction.dragdrop.setupDropZone(productRow, c.id);
                    }
                    tbody.appendChild(productRow);
                });
            }
        });
    }

    /**
 * Rendre les détails du colis sélectionné (VERSION CORRIGÉE - Event Listeners Fonctionnels)
 */
/**
 * Rendre les détails du colis sélectionné (VERSION CORRIGÉE - Event Listeners Fonctionnels)
 */
/**
 * Rendre les détails du colis sélectionné (VERSION CORRIGÉE - Délégation d'événements)
 */
function renderColisDetail() {
    const container = document.getElementById('colisDetail');
    if (!container) {
        debugLog('❌ Élément colisDetail non trouvé');
        return;
    }
    
    const selectedColis = FicheProduction.data.selectedColis();
    
    if (!selectedColis) {
        container.innerHTML = '<div class="empty-state">Sélectionnez un colis pour voir les détails</div>';
        return;
    }

    const weightPercentage = (selectedColis.totalWeight / selectedColis.maxWeight) * 100;
    let weightStatus = 'ok';
    if (weightPercentage > 90) weightStatus = 'danger';
    else if (weightPercentage > 70) weightStatus = 'warning';

    const multipleSection = selectedColis.multiple > 1 ? 
        `<div class="duplicate-controls">
            <span>📦 Ce colis sera créé</span>
            <input type="number" value="${selectedColis.multiple}" min="1" max="100" 
                   class="duplicate-input" id="multipleInput">
            <span>fois identique(s)</span>
            <span style="margin-left: 10px; font-weight: bold;">
                Total: ${(selectedColis.totalWeight * selectedColis.multiple).toFixed(1)} kg
            </span>
        </div>` : '';

    const colisTypeText = selectedColis.isLibre ? 'Colis Libre' : `Colis ${selectedColis.number}`;
    const colisTypeIcon = selectedColis.isLibre ? '📦🆓' : '📦';

    container.innerHTML = `
        <div class="colis-detail-header">
            <h3 class="colis-detail-title">${colisTypeIcon} ${colisTypeText}</h3>
            <button class="btn-delete-colis" id="deleteColisBtn">🗑️ Supprimer</button>
        </div>

        ${multipleSection}

        <div class="constraints-section">
            <div class="constraint-item">
                <div class="constraint-label">Poids:</div>
                <div class="constraint-values">
                    ${selectedColis.totalWeight.toFixed(1)} / ${selectedColis.maxWeight} kg
                </div>
                <div class="constraint-bar">
                    <div class="constraint-progress ${weightStatus}" style="width: ${Math.min(weightPercentage, 100)}%"></div>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 10px; font-weight: bold;">Produits dans ce colis:</div>
        <div class="colis-content" id="colisContent" style="border: 2px dashed #ddd; border-radius: 8px; min-height: 150px; padding: 15px; position: relative;">
            <div class="drop-hint" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #999; font-style: italic; pointer-events: none;">
                ${selectedColis.products.length === 0 ? (selectedColis.isLibre ? 'Colis libre vide' : 'Glissez un produit ici pour l\'ajouter') : ''}
            </div>
        </div>
    `;

    // ✅ SOLUTION : Ajouter les vignettes SANS event listeners individuels
    const colisContent = document.getElementById('colisContent');
    const products = FicheProduction.data.products();
    
    if (selectedColis.products.length > 0) {
        selectedColis.products.forEach((p, index) => {
            const product = products.find(prod => prod.id === p.productId);
            if (!product) return;

            const vignette = FicheProduction.inventory.createProductVignette(product, true, p.quantity);
            
            // ✅ SOLUTION : Bouton supprimer SANS event listener individuel
            const removeBtn = document.createElement('button');
            removeBtn.className = 'btn-remove-line';
            removeBtn.textContent = '×';
            removeBtn.dataset.productId = p.productId;
            removeBtn.dataset.colisId = selectedColis.id;
            removeBtn.style.cssText = `
                position: absolute; top: 5px; left: 5px; background: #dc3545; color: white;
                border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;
                font-size: 12px; display: flex; align-items: center; justify-content: center; z-index: 10;
            `;
            
            vignette.style.position = 'relative';
            vignette.appendChild(removeBtn);

            // ✅ SOLUTION : Input de quantité SANS event listener individuel
            const quantityInput = vignette.querySelector('.quantity-input');
            if (quantityInput) {
                quantityInput.dataset.colisId = selectedColis.id; // Ajouter l'ID du colis
            }

            colisContent.appendChild(vignette);
        });
    }

    // Event listeners pour les contrôles généraux du colis (ces éléments ne changent pas)
    const deleteBtn = document.getElementById('deleteColisBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            await deleteColis(selectedColis.id);
        });
    }

    const multipleInput = document.getElementById('multipleInput');
    if (multipleInput) {
        multipleInput.addEventListener('change', async (e) => {
            await updateColisMultiple(selectedColis.id, e.target.value);
        });
    }

    // Setup drop zone pour le contenu du colis (seulement pour colis normaux)
    if (colisContent && !selectedColis.isLibre && FicheProduction.dragdrop.setupDropZone) {
        FicheProduction.dragdrop.setupDropZone(colisContent, selectedColis.id);
    }
    
    debugLog(`✅ Détails du colis ${selectedColis.id} rendus avec ${selectedColis.products.length} produits - Délégation d'événements active`);
}

// ✅ SOLUTION : Initialiser la délégation d'événements UNE SEULE FOIS
function initializeColisDetailEventDelegation() {
    const container = document.getElementById('colisDetail');
    if (!container) return;
    
    // Supprimer les anciens listeners pour éviter les doublons
    container.removeEventListener('click', handleColisDetailClick);
    container.removeEventListener('change', handleColisDetailChange);
    container.removeEventListener('keydown', handleColisDetailKeydown);
    
    // ✅ DÉLÉGATION : Gérer tous les clics sur les boutons de suppression
    container.addEventListener('click', handleColisDetailClick);
    
    // ✅ DÉLÉGATION : Gérer tous les changements d'inputs de quantité
    container.addEventListener('change', handleColisDetailChange);
    
    // ✅ DÉLÉGATION : Gérer la touche Entrée sur les inputs
    container.addEventListener('keydown', handleColisDetailKeydown);
    
    debugLog('✅ Délégation d\'événements initialisée pour colisDetail');
}

// ✅ SOLUTION : Gestionnaire de clics délégué
function handleColisDetailClick(e) {
    // Bouton supprimer produit
    if (e.target.classList.contains('btn-remove-line')) {
        e.preventDefault();
        e.stopPropagation();
        
        const productId = parseInt(e.target.dataset.productId);
        const colisId = parseInt(e.target.dataset.colisId);
        
        debugLog(`🗑️ Suppression produit ${productId} du colis ${colisId}`);
        removeProductFromColis(colisId, productId);
    }
}

// ✅ SOLUTION : Gestionnaire de changements délégué
function handleColisDetailChange(e) {
    // Input de quantité
    if (e.target.classList.contains('quantity-input')) {
        const productId = parseInt(e.target.dataset.productId);
        const colisId = parseInt(e.target.dataset.colisId);
        const newQuantity = parseInt(e.target.value);
        
        debugLog(`📝 Modification quantité produit ${productId} vers ${newQuantity} dans colis ${colisId}`);
        updateProductQuantity(colisId, productId, newQuantity);
    }
}

// ✅ SOLUTION : Gestionnaire de touches délégué
function handleColisDetailKeydown(e) {
    // Touche Entrée sur input de quantité
    if (e.key === 'Enter' && e.target.classList.contains('quantity-input')) {
        e.target.blur(); // Déclenche l'événement change
    }
}

    /**
     * Initialiser le module colis
     */
    function initializeColisModule() {
        debugLog('📦 Initialisation du module Colis');
        
        // Bouton Nouveau Colis
        const addNewColisBtn = document.getElementById('addNewColisBtn');
        if (addNewColisBtn) {
            addNewColisBtn.addEventListener('click', function(e) {
                e.preventDefault();
                addNewColis();
            });
        }
        
        debugLog('✅ Module Colis initialisé');
    }

    // ============================================================================
    // REGISTRATION DU MODULE (VERSION AMÉLIORÉE)
    // ============================================================================

    const ColisModule = {
        addNewColis: addNewColis, // FONCTION CRITIQUE
        deleteColis: deleteColis,
        selectColis: selectColis,
        addProductToColis: addProductToColis,
        removeProductFromColis: removeProductFromColis,
        updateProductQuantity: updateProductQuantity,
        showDuplicateDialog: showDuplicateDialog,
        updateColisMultiple: updateColisMultiple,
        updateSummaryTotals: updateSummaryTotals,
        renderColisOverview: renderColisOverview,
        renderColisDetail: renderColisDetail,
        initialize: initializeColisModule
    };

    // Fonction d'enregistrement robuste
    function registerColisModule() {
        if (window.FicheProduction) {
            if (window.FicheProduction.registerModule) {
                // Utiliser le nouveau système d'enregistrement
                window.FicheProduction.registerModule('colis', ColisModule);
            } else {
                // Fallback vers l'ancien système
                window.FicheProduction.colis = ColisModule;
                debugLog('📦 Module Colis enregistré (fallback) dans FicheProduction.colis');
            }
            
            // Vérification immédiate
            setTimeout(() => {
                if (window.FicheProduction.colis && window.FicheProduction.colis.addNewColis) {
                    debugLog('✅ addNewColis disponible dans le namespace');
                } else {
                    debugLog('❌ addNewColis toujours non disponible dans le namespace');
                    // Enregistrement forcé si nécessaire
                    window.FicheProduction.colis = ColisModule;
                    debugLog('🔧 Enregistrement forcé du module Colis');
                }
            }, 50);
        } else {
            debugLog('⏳ FicheProduction namespace pas encore disponible, réessai...');
            setTimeout(registerColisModule, 10);
        }
    }

    // Écouter l'événement de disponibilité du core
    if (window.addEventListener) {
        window.addEventListener('FicheProductionCoreReady', registerColisModule);
    }

    // Tenter l'enregistrement immédiat ou différé
    registerColisModule();

    // Export des fonctions pour compatibilité
    window.addNewColis = addNewColis;
    window.deleteColis = deleteColis;
    window.selectColis = selectColis;
    window.addProductToColis = addProductToColis;
    window.removeProductFromColis = removeProductFromColis;
    window.updateProductQuantity = updateProductQuantity;
    window.showDuplicateDialog = showDuplicateDialog;
    window.updateColisMultiple = updateColisMultiple;
    window.updateSummaryTotals = updateSummaryTotals;
    window.renderColisOverview = renderColisOverview;
    window.renderColisDetail = renderColisDetail;

    debugLog('📦 Module Colis chargé et intégré (Version corrigée - Boutons fonctionnels)');
    function initializeColisModule() {
    debugLog('📦 Initialisation du module Colis');
    
    // Bouton Nouveau Colis
    const addNewColisBtn = document.getElementById('addNewColisBtn');
    if (addNewColisBtn) {
        addNewColisBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addNewColis();
        });
    }
    
    // ✅ NOUVEAU : Initialiser la délégation d'événements
    initializeColisDetailEventDelegation();
    
    debugLog('✅ Module Colis initialisé');
}
})();
