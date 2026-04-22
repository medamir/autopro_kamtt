## 1. Hook vs Trigger dans Dolibarr

### Hook:

Un hook permet de modifier ou enrichir l’interface Dolibarr sans toucher au core. Ajout de champs dans un formulaire, modification de pages (card, list…), ajout d’éléments dans les vues ou PDF

Exemple dans le module autopro :
Afficher un champ observations technicien directement dans la fiche d’un ordre de réparation.

### Trigger

Un trigger permet d’exécuter une action automatique lors d’un événement métier Dolibarr.

Exemple dans le module autopro :

La suppression d’un devis déclache automatique la suppression de l’ordre de réparation associé

# 2. Structure minimale d’un module Dolibarr

Pour être reconnu par Dolibarr, un module doit obligatoirement contenir un fichier principal de déclaration.

Structure minimale :
autopro/
└── core/modules/modAutopro.class.php

Structure utilisée dans le projet garage (autopro)

autopro/
├── core/modules/modAutopro.class.php (obligatoire)
├── class/ (DAO + logique métier : Reparation.class.php)
├── sql/ (table llx_autopro_reparation)
├── admin/ (configuration : tarifs, marques…)
├── lang/ (traductions)
├── card.php (fiche ordre de réparation)
├── list.php (liste des ordres)
└── api/ (endpoint REST)
