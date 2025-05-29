# 📄 Fonctionnalité PDF - Fiche de Production

## 🎯 Nouveauté v2.1 : Génération PDF

Votre module Fiche de Production dispose maintenant d'une fonctionnalité complète de génération de PDF basée sur votre maquette HTML/CSS.

### ✨ Fonctionnalités

- **Génération PDF automatique** avec TCPDF (intégré à Dolibarr)
- **Design identique** à votre maquette HTML 
- **Stockage automatique** dans le dossier de la commande client
- **Boutons d'actions** intégrés dans l'interface
- **Aperçu et téléchargement** directement depuis l'interface
- **Données dynamiques** depuis la base de données

### 📋 Données incluses dans le PDF

Le PDF reprend exactement votre maquette avec :

#### En-tête
- Numéro de fiche de production (référence commande)
- Statut de la commande (EN COURS, TERMINÉ, etc.)

#### Informations de livraison (38% gauche)
- **Adresse de livraison** : Contact de livraison défini dans la commande
- **Instructions** : Notes publiques du tiers client

#### Informations commande (62% droite)
- Date de génération
- Nom du client
- Référence chantier (extrafield `ref_chantierfp` ou `ref_chantier`)
- Commentaires (extrafield `commentaires_fp`)

#### Inventaire produits (38% gauche)
- Groupes de produits par nom et couleur
- Quantités commandées (extrafield `nombre` ou qty standard)
- Dimensions longueur×largeur

#### Liste des colis préparés (62% droite)
- Détail de chaque colis avec poids
- Contenu de chaque colis avec références produits
- Quantité par produit dans chaque colis

#### Totaux
- Nombre total de colis préparés  
- Poids total de tous les colis

#### Contrôles de production (3 colonnes)
- **Colisage final** : Cases à cocher pour palettes/fagots/vrac
- **Contrôles qualité** : 5 points de contrôle (dimensions, couleurs, etc.)
- **Signatures** : Production, Contrôle, Expédition + Bobines ID

#### Pied de page
- Date et heure de génération
- Total pièces commandées
- Mention "Document confidentiel"

### 🔧 Installation

#### Fichiers créés
```
ficheproduction/
├── class/
│   └── ficheproductionpdf.class.php    # Classe génération PDF
├── generate_pdf.php                     # Script génération PDF
├── includes/
│   └── ficheproduction_buttons.php     # Boutons d'actions PDF
├── INTEGRATION_PDF.php                  # Instructions d'intégration
└── langs/fr_FR/
    └── ficheproduction.lang             # Traductions mises à jour
```

#### Étapes d'installation

1. **Copier les nouveaux fichiers** dans votre installation Dolibarr

2. **Intégrer les boutons PDF** dans `ficheproduction.php` :
   ```php
   // Ajouter après les autres require_once
   require_once dol_buildpath('/ficheproduction/includes/ficheproduction_buttons.php');
   
   // Remplacer la section "Boutons d'action" par :
   generatePDFButtons($object, $user, $langs, $conf);
   ```

3. **Vérifier les permissions** sur les dossiers de documents

### 🎨 Interface utilisateur

#### Nouveaux boutons dans l'onglet Fiche de Production

- **📄 Générer PDF** : Crée le PDF et l'ouvre dans un nouvel onglet
- **👁️ Voir PDF** : Ouvre le PDF existant (si déjà généré)  
- **⬇️ Télécharger PDF** : Télécharge le PDF via document.php
- **🗑️ Supprimer PDF** : Supprime le fichier (admin uniquement)

#### Informations PDF
Une boîte d'information verte s'affiche quand un PDF existe :
- Nom du fichier  
- Taille du fichier
- Date de génération

### 🔍 Récupération des données

#### Extrafields supportés
Le système récupère automatiquement les extrafields suivants :

**Commande :**
- `ref_chantierfp` ou `ref_chantier` : Référence chantier
- `commentaires_fp` : Commentaires de production

**Lignes de commande :**
- `nombre` : Quantité réelle (priorité sur qty standard)
- `length`, `longueur`, `long` : Longueur du produit
- `width`, `largeur`, `larg` : Largeur du produit  
- `color`, `couleur` : Couleur du produit
- `ref_ligne` : Référence de ligne spécifique

#### Données de production
- Sessions de colisage depuis `llx_ficheproduction_session`
- Colis depuis `llx_ficheproduction_colis`
- Détail des produits depuis `llx_ficheproduction_colis_line`

### 🛠️ Personnalisation

#### Modifier le template PDF
Éditer `class/ficheproductionpdf.class.php` :
- Méthode `_generateContent()` : Structure générale
- Méthode `_generateOrderSummary()` : Section en-tête
- Méthode `_generateMainContent()` : Inventaire + colis
- Méthode `_generateControls()` : Section contrôles

#### Ajouter des extrafields
1. Modifier `_getExtraFieldValue()` pour nouveaux champs
2. Adapter les méthodes de génération selon besoins
3. Mettre à jour les traductions si nécessaire

### 🔒 Sécurité et permissions

#### Contrôles d'accès
- Lecture : Droit `commande->lire` requis
- Génération : Droit `commande->lire` requis
- Suppression : Utilisateur admin uniquement

#### Stockage des fichiers
- Dossier : `/documents/commande/[REF_COMMANDE]/`
- Nom : `[REF_COMMANDE]-fiche-production.pdf`
- Permissions : Héritées de la configuration Dolibarr

### 🐛 Dépannage

#### PDF non généré
1. Vérifier les permissions d'écriture sur `/documents/commande/`
2. Contrôler les logs Dolibarr pour erreurs TCPDF
3. S'assurer que l'extension TCPDF est disponible

#### Données manquantes
1. Vérifier que les extrafields existent et sont remplis
2. Contrôler que les données de production sont sauvegardées
3. Tester la méthode `loadColisageData()` du manager

#### Boutons non visibles
1. Vérifier l'inclusion du fichier `ficheproduction_buttons.php`
2. Contrôler l'appel à `generatePDFButtons()`
3. Vérifier les permissions utilisateur

### 📊 Exemple de génération

```php
// Exemple d'utilisation de la classe PDF
$pdfGenerator = new FicheProductionPDF($db);

// Générer le PDF
$result = $pdfGenerator->write_file(
    $object,        // Commande Dolibarr
    $outputlangs,   // Langue de sortie
    '',             // Template path (optionnel)
    0,              // Hide details
    0,              // Hide desc  
    0               // Hide ref
);

if ($result > 0) {
    // PDF généré avec succès
    echo "PDF créé : ".$object->ref."-fiche-production.pdf";
} else {
    // Erreur de génération
    echo "Erreur : ".$pdfGenerator->error;
}
```

### 🔄 Intégration avec l'existant

Le système PDF s'intègre parfaitement avec votre module existant :
- **Aucune modification** des tables de base de données
- **Compatible** avec l'architecture modulaire JavaScript
- **Respecte** le système de permissions Dolibarr
- **Utilise** les traductions existantes du module

### 📈 Évolutions futures

Fonctionnalités prévues :
- **Templates personnalisables** via interface admin
- **Export en plusieurs formats** (PDF, Excel, CSV)
- **Envoi automatique par email** après génération
- **Intégration** avec les expéditions Dolibarr
- **Historique** des générations PDF

---

## 🎉 Récapitulatif

Votre module Fiche de Production dispose maintenant de :

✅ **Génération PDF complète** basée sur votre maquette  
✅ **Interface utilisateur intégrée** avec boutons d'actions  
✅ **Récupération automatique** de toutes les données  
✅ **Stockage sécurisé** dans l'arborescence Dolibarr  
✅ **Gestion des permissions** et contrôles d'accès  
✅ **Traductions françaises** complètes  

**Le PDF est prêt à l'emploi et respecte exactement votre maquette HTML !**
