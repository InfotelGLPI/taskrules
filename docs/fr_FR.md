# Documentation — Plugin TaskRules pour GLPI

## Présentation

Le plugin **TaskRules** permet d'automatiser la mise à jour des éléments d'inventaire (matériels) liés à un ticket lorsqu'une **tâche** est ajoutée à ce ticket ou que son statut passe à « Fait ». Chaque règle est attachée à un **modèle de tâche** (`TaskTemplate`) : dès qu'une tâche de ticket utilisant ce modèle est créée ou clôturée, la règle évalue ses conditions sur les matériels liés au ticket, puis applique les actions configurées (changement de statut, de localisation, d'utilisateur, de groupe, de contact).

**Licence** : GPLv3+  
**Auteur** : Edmond Doudard / Infotel  
**Dépôt** : https://github.com/InfotelGLPI/taskrules

---

## Cas d'usage

Exemple typique : un technicien ajoute la tâche « Mise au rebut » sur un ticket lié à un ordinateur. Grâce à une règle TaskRules attachée au modèle de tâche « Mise au rebut », l'ordinateur lié au ticket voit automatiquement son statut passer à « En rebut » et sa localisation vidée, sans intervention manuelle.

---

## Fonctionnalités

- Automatisation déclenchée par les **tâches de ticket** (`TicketTask`)
- Une règle par **modèle de tâche** (`TaskTemplate`)
- Deux déclencheurs : à l'**ajout** d'une tâche, ou au passage du statut de la tâche à **Fait**
- Conditions de filtrage par type de matériel et par champ (Ordinateur, Écran, Périphérique, Imprimante, Téléphone)
- Actions sur les matériels liés au ticket :
  - **Statut** (vider ou affecter un statut précis)
  - **Localisation** (vider, reprendre celle du demandeur, ou affecter une localisation précise)
  - **Contact / nom alternatif**
  - **Utilisateur**
  - **Groupe**
- Options de sécurité : empêcher la création de la tâche en cas d'erreur, ou empêcher le passage « à faire → fait »
- Fenêtre modale de **résultats** récapitulant les modifications appliquées
- **Journalisation** des exécutions de règles
- Gestion des droits par profil

---

## Prérequis

| Composant | Version |
|-----------|---------|
| GLPI      | 11.0    |
| PHP       | ≥ 8.1   |

---

## Installation

1. Télécharger l'archive depuis https://github.com/InfotelGLPI/taskrules/releases
2. Décompresser dans le dossier `marketplace/` (ou `plugins/`) de votre instance GLPI
3. Accéder à **Configuration > Plugins**
4. Cliquer sur **Installer** puis **Activer** pour le plugin TaskRules

---

## Droits et profils

Le plugin ajoute un droit unique configurable dans **Administration > Profils**, onglet **Task rules**.

| Droit | Clé interne |
|-------|------------|
| Task rules | `plugin_taskrules` |

La configuration générale est aussi accessible aux profils disposant du droit `config`.

---

## Configuration d'une règle

Une règle se configure **depuis un modèle de tâche** :

1. Aller dans **Configuration > Intitulés > Modèles de tâches** (ou via la gestion des tâches).
2. Ouvrir (ou créer) un modèle de tâche.
3. Aller sur l'onglet **Task rules**.
4. Renseigner les quatre sections (présentées sous forme d'accordéon) décrites ci-dessous.
5. Enregistrer.

### 1. Paramètres

| Option | Description |
|--------|-------------|
| **Actif** | Active ou désactive la règle |
| **Empêcher la création de la tâche en cas d'erreur** | Bloque l'ajout de la tâche si l'application de la règle échoue |
| **Empêcher le changement de statut de la tâche en cas d'erreur** | Empêche le passage « à faire → fait » si la règle échoue |

### 2. Conditions

Définit les critères que les matériels liés au ticket doivent remplir pour que les actions s'appliquent. Les conditions s'expriment par type de matériel — **Ordinateur, Écran, Périphérique, Imprimante, Téléphone** — à l'aide du moteur de recherche GLPI (mêmes critères que la recherche standard).

Un matériel lié au ticket qui ne satisfait pas les conditions est signalé comme « non concerné » dans les résultats, sans être modifié.

### 3. Déclencheurs

| Déclencheur | Description |
|-------------|-------------|
| **Activer lorsqu'une tâche est ajoutée au ticket** | La règle s'exécute à la création de la tâche (`trigger_creation`) |
| **Déclencher lors du passage du statut de la tâche de « à faire » à « fait »** | La règle s'exécute quand la tâche est marquée comme terminée (`trigger_update`) |

Les deux déclencheurs peuvent être activés simultanément.

### 4. Actions

Actions appliquées à chaque matériel lié au ticket satisfaisant les conditions :

| Action | Valeurs possibles |
|--------|-------------------|
| **Statut** | Ne pas changer / Vider le champ / Affecter un statut précis |
| **Localisation** | Ne pas changer / Vider le champ / Affecter la localisation du demandeur du ticket / Affecter une localisation précise |
| **Nom alternatif (contact)** | Ne pas changer / Vider le champ |
| **Utilisateur** | Affecter l'utilisateur (selon configuration) |
| **Groupe** | Affecter le groupe (selon configuration) |

---

## Fonctionnement à l'exécution

1. Un technicien ajoute une tâche à un ticket (ou marque une tâche existante comme **Fait**).
2. Si une règle **active** est associée au modèle de cette tâche et que le déclencheur correspondant est activé, le plugin :
   - récupère tous les matériels liés au ticket (`Item_Ticket`) ;
   - évalue les **conditions** sur chacun d'eux ;
   - applique les **actions** aux matériels qui satisfont les conditions.
3. Une **fenêtre modale de résultats** s'affiche, récapitulant les éléments modifiés et les éventuelles erreurs (matériels non concernés, aucun matériel lié, etc.).
4. Si l'option correspondante est activée et qu'une erreur survient, la création de la tâche ou le changement de son statut peut être bloqué.

> Lorsque le statut d'une tâche est piloté par une règle (`trigger_update`), la case à cocher d'état de la tâche est désactivée dans l'interface du ticket, avec l'info-bulle « Statut géré par une règle ».

---

## Journal (Log)

Le plugin enregistre les exécutions de règles. Le journal permet de consulter l'historique des modifications appliquées aux matériels par les règles, accessible via les écrans de log du plugin (`front/log.php`).

---

## Traductions disponibles

| Langue | Code |
|--------|------|
| Français | fr_FR |
| Anglais | en_GB |

Contribuer aux traductions : https://explore.transifex.com/infotelGLPI/GLPI_taskrules/

---

## Support et contribution

- **Signaler un bug** : https://github.com/InfotelGLPI/taskrules/issues
- **Dépôt source** : https://github.com/InfotelGLPI/taskrules
- **Blog Infotel** : https://blogglpi.infotel.com
