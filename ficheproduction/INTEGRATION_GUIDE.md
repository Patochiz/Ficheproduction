# 🛠️ Guide d'intégration PDF - Modifications exactes

## 📍 Modifications à apporter dans `ficheproduction.php`

### 1️⃣ MODIFICATION 1 : Ajouter le require_once

**Localisation :** Lignes 25-35 (avec les autres require_once)

**Rechercher cette section :**
```php
// Load FicheProduction classes
require_once dol_buildpath('/ficheproduction/class/ficheproductionmanager.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductionsession.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductioncolis.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductioncolisline.class.php');
```

**Transformer en :**
```php
// Load FicheProduction classes
require_once dol_buildpath('/ficheproduction/class/ficheproductionmanager.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductionsession.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductioncolis.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductioncolisline.class.php');

// Load PDF buttons helper
require_once dol_buildpath('/ficheproduction/includes/ficheproduction_buttons.php');
```

---

### 2️⃣ MODIFICATION 2 : Remplacer les boutons d'action

**Localisation :** Vers les lignes 800-810 (chercher "tabsAction")

**Rechercher cette section complète :**
```php
// Boutons d'action
print '<div class="tabsAction">';
if ($userCanEdit) {
    print '<a class="butAction" href="javascript:saveColisage();" id="saveColisageBtn">💾 ' . $langs->trans("Save") . '</a>';
}
print '<a class="butAction" href="javascript:preparePrint();">' . $langs->trans("PrintButton") . '</a>';
print '</div>';
```

**Remplacer par :**
```php
// Boutons d'action avec PDF
generatePDFButtons($object, $user, $langs, $conf);
```

---

## 🎯 Méthode de recherche rapide

### Dans votre éditeur de code :

1. **Ouvrir** `ficheproduction/ficheproduction.php`

2. **Rechercher** (Ctrl+F) : `ficheproductioncolisline.class.php`
   - Ajouter la ligne `require_once` juste après

3. **Rechercher** (Ctrl+F) : `tabsAction`
   - Remplacer toute la section des boutons

### Validation visuelle :

**AVANT la modification 1 :**
```php
require_once dol_buildpath('/ficheproduction/class/ficheproductioncolisline.class.php');

// Load translations
$langs->loadLangs(array('orders', 'products', 'companies'));
```

**APRÈS la modification 1 :**
```php
require_once dol_buildpath('/ficheproduction/class/ficheproductioncolisline.class.php');

// Load PDF buttons helper
require_once dol_buildpath('/ficheproduction/includes/ficheproduction_buttons.php');

// Load translations
$langs->loadLangs(array('orders', 'products', 'companies'));
```

**AVANT la modification 2 :**
```php
print '</div>'; // Fermeture div layout flexbox
// FIN NOUVELLE SECTION

// Boutons d'action
print '<div class="tabsAction">';
if ($userCanEdit) {
    print '<a class="butAction" href="javascript:saveColisage();" id="saveColisageBtn">💾 ' . $langs->trans("Save") . '</a>';
}
print '<a class="butAction" href="javascript:preparePrint();">' . $langs->trans("PrintButton") . '</a>';
print '</div>';

<!-- Console de debug -->
```

**APRÈS la modification 2 :**
```php
print '</div>'; // Fermeture div layout flexbox
// FIN NOUVELLE SECTION

// Boutons d'action avec PDF
generatePDFButtons($object, $user, $langs, $conf);

<!-- Console de debug -->
```

---

## ✅ Validation des modifications

Après les modifications, vous devriez avoir :

1. **Nouveau require_once** ajouté avec les autres classes
2. **Section boutons** remplacée par un seul appel de fonction
3. **Fichier fonctionnel** sans erreur de syntaxe

### Test rapide :
- Ouvrir une commande client
- Aller dans l'onglet "Fiche de Production"  
- Vérifier la présence du bouton "📄 Générer PDF"

---

## 🚨 Points d'attention

- **Ne pas oublier** le point-virgule après le require_once
- **Remplacer toute la section** des boutons, pas seulement une partie
- **Conserver** l'indentation existante
- **Sauvegarder** une copie du fichier original avant modification

**Les modifications sont minimales et non-intrusives !** 🎉
