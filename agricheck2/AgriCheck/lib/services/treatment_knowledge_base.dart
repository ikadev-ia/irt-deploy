class TreatmentAdvice {
  const TreatmentAdvice({
    this.symptoms = const <String>[],
    this.causes = const <String>[],
    this.biological = const <String>[],
    this.chemical = const <String>[],
    this.prevention = const <String>[],
    this.dosage = '',
  });

  final List<String> symptoms;
  final List<String> causes;
  final List<String> biological;
  final List<String> chemical;
  final List<String> prevention;
  final String dosage;
}

class TreatmentKnowledgeBase {
  static TreatmentAdvice forDisease(String diseaseName) {
    final normalized = diseaseName.toLowerCase();
    if (normalized.contains('anthrac')) {
      return const TreatmentAdvice(
        symptoms: <String>[
          'Taches brunes ou noires sur feuilles, fruits ou tiges.',
          'Dessèchement progressif des tissus attaques.',
        ],
        causes: <String>[
          'Champignons favorises par humidite elevee et pluies repetees.',
        ],
        biological: <String>[
          'Retirer les parties tres atteintes.',
          'Pulveriser un biostimulant ou extrait vegetal homologue si disponible.',
        ],
        chemical: <String>[
          'Utiliser un fongicide homologue localement contre anthracnose apres avis technique.',
        ],
        prevention: <String>[
          'Espacer les plants pour aerer la culture.',
          'Eviter l arrosage direct sur le feuillage.',
        ],
        dosage:
            'Respecter strictement l etiquette du produit homologue et les delais avant recolte.',
      );
    }
    if (normalized.contains('mildiou') || normalized.contains('mildew')) {
      return const TreatmentAdvice(
        symptoms: <String>[
          'Taches huileuses ou jaunatres.',
          'Duvet blanc ou gris au revers des feuilles.',
        ],
        causes: <String>[
          'Humidite forte, rosée persistante et mauvaise aeration.',
        ],
        biological: <String>[
          'Supprimer les feuilles fortement infectees.',
          'Ameliorer l aeration et limiter l humidite sur feuilles.',
        ],
        chemical: <String>[
          'Appliquer un fongicide anti-mildiou homologue si le risque est eleve.',
        ],
        prevention: <String>[
          'Choisir des varietes tolerantes quand elles existent.',
          'Eviter les plantations trop denses.',
        ],
        dosage:
            'Suivre la dose indiquee par le fournisseur et alterner les familles de fongicides.',
      );
    }
    if (normalized.contains('oidium') ||
        normalized.contains('oïdium') ||
        normalized.contains('powdery')) {
      return const TreatmentAdvice(
        symptoms: <String>[
          'Poudre blanche sur les feuilles.',
          'Feuilles deformees ou croissance ralentie.',
        ],
        causes: <String>['Alternance humidite nocturne et chaleur en journee.'],
        biological: <String>[
          'Retirer les feuilles atteintes.',
          'Utiliser soufre ou solution biologique homologuee si compatible avec la culture.',
        ],
        chemical: <String>[
          'Fongicide anti-oïdium homologue en cas de progression rapide.',
        ],
        prevention: <String>[
          'Limiter les exces d azote.',
          'Espacer les plants et surveiller les jeunes pousses.',
        ],
        dosage:
            'Ne pas appliquer de soufre en forte chaleur; suivre l etiquette locale.',
      );
    }
    if (normalized.contains('rouille') || normalized.contains('rust')) {
      return const TreatmentAdvice(
        symptoms: <String>[
          'Pustules orange, jaunes ou brunes.',
          'Jaunissement puis chute des feuilles.',
        ],
        causes: <String>['Spores dispersees par vent et humidite.'],
        biological: <String>[
          'Enlever les feuilles tres touchees.',
          'Nettoyer les residus de culture apres recolte.',
        ],
        chemical: <String>[
          'Fongicide homologue contre rouille si la maladie s etend.',
        ],
        prevention: <String>[
          'Utiliser semences saines.',
          'Eviter la monoculture continue sur la meme parcelle.',
        ],
        dosage:
            'Adapter la dose a la culture et au stade vegetatif avec un conseiller agricole.',
      );
    }
    if (normalized.contains('fusario')) {
      return const TreatmentAdvice(
        symptoms: <String>[
          'Fletrissement malgre un sol humide.',
          'Brunissement des vaisseaux internes.',
        ],
        causes: <String>[
          'Champignon du sol favorise par sols contamines et drainage faible.',
        ],
        biological: <String>[
          'Isoler les plants touches.',
          'Ameliorer le drainage et incorporer de la matiere organique saine.',
        ],
        chemical: <String>[
          'Traitement chimique souvent limite; privilegier semences et plants certifies.',
        ],
        prevention: <String>[
          'Rotation longue des cultures.',
          'Desinfecter outils et eviter de deplacer la terre contaminee.',
        ],
        dosage:
            'Demander confirmation terrain avant toute depense en traitement.',
      );
    }
    if (normalized.contains('mosaic') ||
        normalized.contains('mosaïque') ||
        normalized.contains('mosaique')) {
      return const TreatmentAdvice(
        symptoms: <String>[
          'Motifs vert clair et vert fonce en mosaïque.',
          'Feuilles deformees, nanisme possible.',
        ],
        causes: <String>[
          'Virus souvent transmis par insectes piqueurs ou semences/plants contamines.',
        ],
        biological: <String>[
          'Arracher et detruire les plants tres atteints.',
          'Controler les vecteurs avec methodes integrees.',
        ],
        chemical: <String>[
          'Aucun traitement curatif direct contre le virus; traiter seulement les vecteurs si necessaire.',
        ],
        prevention: <String>[
          'Utiliser semences saines.',
          'Desherber autour de la parcelle et installer filets si possible.',
        ],
        dosage:
            'Ne pas depenser en curatif antiviral; viser prevention et controle des vecteurs.',
      );
    }
    return const TreatmentAdvice(
      biological: <String>[
        'Isoler la plante ou la zone suspecte.',
        'Retirer les tissus fortement atteints avec des outils propres.',
      ],
      chemical: <String>[
        'Faire confirmer le diagnostic avant traitement chimique.',
      ],
      prevention: <String>[
        'Surveiller l evolution pendant 48 a 72 heures.',
        'Eviter l exces d eau et ameliorer l aeration.',
      ],
      dosage:
          'Le dosage depend du produit homologue, de la culture et du stade de croissance.',
    );
  }
}
