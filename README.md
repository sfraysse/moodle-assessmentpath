> **Assessment Path** est un plugin pour Moodle permettant la conception de parcours d'évaluation avec tests initiaux et tests de remédiation. Les évaluations sont des contenus SCORM Lite (cf http://scormlite.com).


# Versions

Vous êtes sur la page de la **version 3.4.5** du plugin Assessment Path, dernière version compatible avec **Moodle 3.4**.

Ce plugin n'existe pas encore pour les versions plus récentes de Moodle.


# Installation


## Pré-requis

- Moodle version 3.4.x
- [SCORM Lite 3.4.5+](https://github.com/sfraysse/moodle-scormlite/tree/3.4)


## Procédure d'installation

### Plugin principal

- Télécharger la dernière version du plugin : https://github.com/sfraysse/moodle-assessmentpath/archive/v3.4.6.zip.
- Dans `Moodle > Administration > Plugins > Install plugins`, importer le fichier ZIP du plugin.
- Suivre la procédure d'installation.

### Plugins complémentaires

Bien que complémentaires, l'installation de ces plugins est **obligatoire** pour assurer le bon fonctionnement d'Assessment Path. Répéter la procédure d'installation pour les plugins :

- Bloc Assessment Path : [dernière version](https://github.com/sfraysse/moodle-assessmentpath-block/archive/v3.4.0.zip)
- Rapport de cours Assessment Path : [dernière version](https://github.com/sfraysse/moodle-assessmentpath-report/archive/v3.4.0.zip)



## Paramétrage général du plugin

En dehors des réglages par défaut pour les activités nouvellement créées, on trouve :

- **Display close button** - Afficher un bouton de sortie de l'activité au dessus du Player SCORM lorsque l'affichage est non fenêtré.

- **Display rank** - Afficher une colonne de classement des apprenants dans la page de suivi.


## Permissions associées au plugin

En dehors des permissions définies par le plugin SCORM Lite, on trouve :

- **mod/assessmentpath:notifycompletion** - Autoriser l’utilisateur à recevoir une notification lorsqu'un apprenant a terminé le parcours. Cette permission est assignée par défaut aux rôles `Course Manager`, `Editing Teacher`, `Teacher`. 


# Utilisation 


## Paramétrage d'un parcours

En dehors des réglages communs à toutes les activités Moodle, on trouve :

- **Code** - Code de l'activité repris dans la page de suivi.

- **Reporting colors** - Couleurs à appliquer dans la page de suivi.

Par ailleurs, l'activité doit être restreinte à un groupe d'élèves pour pouvoir afficher le rapport de suivi correspondant :

- **Restrict access** - Restreindre l'accès à un groupe d'élèves.

Pour que les enseignants soient notifiés lorsqu'un apprenant termine un parcours, il faut aussi régler la complétion de l'activité :

- **Activity completion** - Choisir `Show activity as complete when conditions are met`, puis cocher la case `Student must view this activity to complete it`.


## Paramétrage des tests du parcours

Les réglages applicables à chaque test du parcours reprennent globalement ceux utilisés par le plugin SCORM Lite.
En dehors de ces réglages, on trouve :

- **Availability / From / Until** - Possibilité de choisir la valeur `Automatic` pour les tests de remédiation. Le test est alors accessibles dès que l'apprenant échoue au test initial correspondant.


## Consultation de l'activité

L'activité `Assessment Path` présente l'intégralité du parcours, décomposé en étapes, puis en test initial vs test de remédiation. Le fonctionnement de chaque test est similaire au fonctionnement des activités SCORM Lite.


## Rapports de suivi

Lorsque l’utilisateur est autorisé à accéder aux rapports de suivi, un onglet `Report` est affiché. Il donne accès à plusieurs rapports de suvi :

- Suivi d’un groupe, tous parcours confondus au sein d'un cours ;
- Suivi d’un groupe pour un parcours ;
- Suivi d’un groupe pour une étape du parcours ;
- Suivi d’un apprenant, tous parcours confondus au sein d'un cours ;
- Suivi d’un apprenant pour un parcours ;
- Statistiques des tests Quetzal (optionnel) ;

Par ailleurs, le bloc `Assessment Path progress` peut être ajouté au cours. Il présente la progression des différents groupes dans les parcours d’évaluation.

Enfin, le rapport de `Suivi d’un groupe pour une étape du parcours` présente une fonction de modification des scores.


## Notifications

Le plugin Assessment Path permet la notification des enseignants à chaque fois qu'un apprenant termine un parcours. Cette notification est toutefois désactivée par défaut, à la fois pour des raisons d'historique et de performances.

Pour activer la notification :

1. La gestion de complétion doit être activée dans Moodle, dans le cours concerné, et au niveau de l'activité concernée (cf plus haut).

2. Les utilisateurs souhaitant recevoir les notifications doivent avoir la permission `mod/assessmentpath:notifycompletion`. Par défaut, c'est le cas des rôles `Course Manager`, `Editing Teacher`, `Teacher`. 

3. Les utilisateurs souhaitant recevoir les notifications doivent aller dans leur préférences : `Notification preferences` > `Assessment Path completion`; et activer le mode de notification souhaité (Web et/ou Email).

Noter aussi que les notifications ne sont envoyées que lors de l'exécution de la tâche CRON de Moodle. Il peut donc y avoir un décalage plus ou moins important en fonction des paramétrages de la plateforme. 



