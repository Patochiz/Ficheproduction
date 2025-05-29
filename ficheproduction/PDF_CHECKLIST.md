# ✅ Checklist d'installation et de test - Fonctionnalité PDF

## 📋 Installation

### 1. Vérification des fichiers créés
- [ ] `class/ficheproductionpdf.class.php` - Classe principale de génération PDF
- [ ] `generate_pdf.php` - Script de génération et gestion des actions PDF
- [ ] `includes/ficheproduction_buttons.php` - Fonction d'affichage des boutons
- [ ] `langs/fr_FR/ficheproduction.lang` - Traductions mises à jour
- [ ] `PDF_README.md` - Documentation complète
- [ ] `INTEGRATION_PDF.php` - Instructions d'intégration

### 2. Modification du fichier principal
- [ ] Ouvrir `ficheproduction/ficheproduction.php`
- [ ] Ajouter cette ligne après les autres `require_once` :
  ```php
  require_once dol_buildpath('/ficheproduction/includes/ficheproduction_buttons.php');
  ```
- [ ] Remplacer la section "Boutons d'action" (lignes ~800-805) par :
  ```php
  generatePDFButtons($object, $user, $langs, $conf);
  ```

### 3. Permissions système
- [ ] Vérifier que le dossier `/documents/commande/` existe
- [ ] Vérifier les permissions d'écriture sur ce dossier
- [ ] Tester la création d'un fichier dans ce dossier

## 🧪 Tests fonctionnels

### 1. Test avec une commande existante
- [ ] Ouvrir une commande client avec des produits
- [ ] Aller dans l'onglet "Fiche de Production"
- [ ] Vérifier la présence du bouton "📄 Générer PDF"

### 2. Test de génération PDF
- [ ] Cliquer sur "Générer PDF"
- [ ] Vérifier que le PDF s'ouvre dans un nouvel onglet
- [ ] Contrôler que le fichier est créé dans `/documents/commande/[REF]/`

### 3. Test des données dans le PDF
- [ ] **En-tête** : Référence commande + statut
- [ ] **Adresse livraison** : Contact de livraison ou adresse client
- [ ] **Instructions** : Notes publiques du tiers
- [ ] **Infos commande** : Date, client, réf chantier, commentaires
- [ ] **Inventaire** : Produits groupés par nom/couleur avec quantités
- [ ] **Colis** : Liste des colis préparés (si données existantes)
- [ ] **Totaux** : Nombre de colis et poids total
- [ ] **Contrôles** : 3 colonnes avec cases à cocher et signatures
- [ ] **Pied de page** : Date génération, total pièces

### 4. Test des boutons additionnels
- [ ] Après génération, vérifier présence des boutons :
  - [ ] "👁️ Voir PDF"
  - [ ] "⬇️ Télécharger PDF"
  - [ ] "🗑️ Supprimer PDF" (si admin)
- [ ] Tester le téléchargement via document.php
- [ ] Tester la suppression (si admin)

### 5. Test avec différents types de données

#### Commande avec extrafields
- [ ] Tester avec `ref_chantierfp` rempli
- [ ] Tester avec `commentaires_fp` rempli
- [ ] Tester avec contact de livraison défini

#### Lignes avec extrafields
- [ ] Tester avec extrafield `nombre` (quantité)
- [ ] Tester avec dimensions (`length`, `width`)
- [ ] Tester avec couleurs (`color`)

#### Données de production
- [ ] Tester avec des colis créés via l'interface
- [ ] Vérifier le calcul des poids et quantités
- [ ] Contrôler l'affichage des produits dans les colis

## 🔍 Tests de robustesse

### 1. Gestion des erreurs
- [ ] Tester avec une commande sans produits
- [ ] Tester avec des extrafields manquants
- [ ] Tester avec des données de production vides
- [ ] Vérifier les messages d'erreur appropriés

### 2. Permissions
- [ ] Tester avec un utilisateur sans droit de lecture commande
- [ ] Tester la suppression PDF avec un utilisateur non-admin
- [ ] Vérifier les contrôles d'accès

### 3. Performances
- [ ] Tester avec une commande contenant beaucoup de produits
- [ ] Vérifier le temps de génération
- [ ] Contrôler la taille du fichier généré

## 🐛 Dépannage courant

### Problèmes possibles et solutions

#### PDF non généré
- **Cause** : Permissions insuffisantes
- **Solution** : `chmod 755 /var/www/dolibarr/documents/commande/`

#### Boutons non visibles
- **Cause** : Fichier d'intégration non inclus
- **Solution** : Vérifier le `require_once` et l'appel `generatePDFButtons()`

#### Données manquantes dans le PDF
- **Cause** : Extrafields non définis
- **Solution** : Créer les extrafields ou adapter la classe PDF

#### Erreur TCPDF
- **Cause** : Bibliothèque non disponible
- **Solution** : Vérifier l'installation TCPDF dans Dolibarr

### Logs de débogage
- [ ] Vérifier `/var/log/dolibarr.log` pour erreurs
- [ ] Utiliser `dol_syslog()` pour tracer les problèmes
- [ ] Tester en mode debug Dolibarr

## 📊 Validation finale

### Checklist de validation
- [ ] ✅ PDF généré avec succès
- [ ] ✅ Toutes les données présentes et correctes
- [ ] ✅ Design conforme à la maquette
- [ ] ✅ Boutons fonctionnels
- [ ] ✅ Permissions respectées
- [ ] ✅ Fichier stocké au bon endroit
- [ ] ✅ Téléchargement fonctionnel
- [ ] ✅ Suppression fonctionnelle (admin)

### Métriques de qualité
- [ ] **Performance** : Génération < 5 secondes
- [ ] **Taille** : Fichier PDF < 2 Mo
- [ ] **Compatibilité** : Lisible sur tous lecteurs PDF
- [ ] **Responsive** : Adapt mobile/desktop si nécessaire

## 🎉 Résultat attendu

Une fois tous les tests validés, vous devriez avoir :

1. **Interface complète** avec boutons PDF intégrés
2. **Génération automatique** de PDF fidèle à votre maquette
3. **Gestion des fichiers** avec aperçu et téléchargement
4. **Sécurité** avec contrôles d'accès appropriés
5. **Documentation** complète pour maintenance

**Status final** : ✅ Module Fiche de Production v2.1 avec PDF opérationnel !

---

## 📞 Support

En cas de problème :
1. Consulter le `PDF_README.md` pour la documentation complète
2. Vérifier les logs Dolibarr pour les erreurs
3. Tester étape par étape avec cette checklist
4. Valider les permissions et l'environnement

**La fonctionnalité PDF est maintenant intégrée et prête à l'emploi !** 🚀
