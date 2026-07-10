
import 'dart:convert';
import 'dart:typed_data';
import 'package:agricheck/models/diagnosis_result.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../theme/app_theme.dart';
import 'analysis_progress_screen.dart';


//import 'dart:io';
import 'package:google_generative_ai/google_generative_ai.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
Future<String?> analyserMaladieAvecGemini(XFile imageFile) async {
  // Récupération sécurisée de la clé API Gemini
  // final String? geminiApiKey = dotenv.env['GEMINI_API_KEY'];
  // if (geminiApiKey == null || geminiApiKey.trim().isEmpty) {
  //   return "Erreur : Clé GEMINI_API_KEY introuvable ou non configurée dans l'environnement.";
  // }
  
  //const String geminiApiKey = "AQ.Ab8RN6JrYW7LrJ-2caeHpfOcxgMf1_LKY5qydB0h9NJalmVIig";
  const String geminiApiKey = "ICI LA CLE API";
  

  try {
    // 2. Initialiser le modèle Gemini (on utilise gemini-2.5-flash ou gemini-1.5-flash pour le traitement d'images)
    final model = GenerativeModel(
      model: 'gemini-2.5-flash',
      apiKey: geminiApiKey,
    );

    // 3. Convertir l'image prise par le téléphone en octets (bytes) pour l'IA
    final imageBytes = await imageFile.readAsBytes();
    final imagePart = DataPart('image/jpeg', imageBytes);

    // 4. Définir le prompt (les consignes de diagnostic pour AgriCheck)
    final prompt = TextPart("""
Tu es un expert en phytopathologie.

Avant toute analyse, vérifie si l'image contient réellement une plante, une feuille, une fleur, un fruit, une herbe ou un arbre.

Si ce n'est PAS une plante (personne, animal, voiture, téléphone, maison, objet...), réponds UNIQUEMENT par ce JSON :

{
  "isPlant": false,
  "message": "Cette image ne représente pas une plante. Veuillez photographier une plante, une feuille, une fleur, une herbe ou un arbre."
}

Si c'est une plante, réponds UNIQUEMENT par un JSON valide :

{
  "isPlant": true,
  "plantName": "",
  "scientificName": "",
  "family": "",
  "plantConfidence": "",
  "diseaseName": "",
  "diseaseConfidence": 95,
  "riskLevel": "",
  "isHealthy": false,
  "plantDescription": "",
  "symptoms": [
    "..."
    "..."
    "..."
    ],
  "causes": [
   "..."
   "..."
   "..."
   ],
  "biologicalTreatments": [
   "..."
   "..."],
  "chemicalTreatments": [
   "..."
   "..."],
  "prevention": [
   "..."
   "..."]
}

Ne renvoie aucun texte en dehors du JSON.
""");

    print("Envoi de l'image à Gemini...");

    final response = await model.generateContent([
      Content.multi([prompt, imagePart])
    ]);

    return response.text;

  } catch (e) {
    print(e);
    return "Erreur : $e";
  }
}

class AnalysisScreen extends StatefulWidget {
  const AnalysisScreen({super.key});

  @override
  State<AnalysisScreen> createState() => _AnalysisScreenState();
}

class _AnalysisScreenState extends State<AnalysisScreen> {
  final ImagePicker _picker = ImagePicker();
  XFile? _image;
  Uint8List? _previewBytes;
  String? _error;

  Future<void> _pick(ImageSource source) async {
    final image = await _picker.pickImage(source: source, imageQuality: 92);
    if (image == null) {
      return;
    }
    final bytes = await image.readAsBytes();
    setState(() {
      _image = image;
      _previewBytes = bytes;
      _error = null;
    });
  }

  Future<void> _analyze() async {
    final image = _image;
    if (image == null) {
      setState(
        () => _error =
            'Choisissez une photo de plante ou d arbre avant de lancer l analyse.',
      );
      return;
    }
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) =>
            AnalysisProgressScreen(image: image, previewBytes: _previewBytes),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
      children: <Widget>[
        Card(
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  'Scanner une plante ou un arbre',
                  style: Theme.of(
                    context,
                  ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
                ),
                const SizedBox(height: 6),
                const Text(
                  'Photographiez une plante ou importez une image pour lancer le diagnostic.',
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),
        DecoratedBox(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: const Color(0xFFD8E7DB)),
          ),
          child: SizedBox(
            height:180,
            child: _previewBytes == null
                ? const Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: <Widget>[
                        Icon(
                          Icons.add_photo_alternate_outlined,
                          size: 100,
                          color: AppTheme.leaf,
                        ),
                        SizedBox(height: 16),
                        Text('Photo de plante, feuille, fruit ou arbre'),
                      ],
                    ),
                  )
                : ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Image.memory(_previewBytes!, fit: BoxFit.cover),
                  ),
          ),
        ),
        const SizedBox(height: 12),
        Row(
          children: <Widget>[
            Expanded(
              child: OutlinedButton.icon(
                onPressed: () => _pick(ImageSource.camera),
                icon: const Icon(Icons.camera_alt_outlined),
                label: const Text('Camera'),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: OutlinedButton.icon(
                onPressed: () => _pick(ImageSource.gallery),
                icon: const Icon(Icons.photo_library_outlined),
                label: const Text('Galerie'),
              ),
            ),
          ],
        ),
        if (_image != null) ...<Widget>[
          const SizedBox(height: 10),
          FilledButton.icon(
            onPressed: () => setState(() {
              _image = null;
              _previewBytes = null;
              _error = null;
            }),
            icon: const Icon(Icons.refresh),
            label: const Text('Reprendre'),
          ),
        ],
        const SizedBox(height: 14),
        FilledButton.icon(
          onPressed: _analyze,
          icon: const Icon(Icons.biotech_outlined),
          label: const Text('Analyser'),
        ),
        if (_error != null) ...<Widget>[
          const SizedBox(height: 12),
          DecoratedBox(
            decoration: BoxDecoration(
              color: const Color(0xFFFFF3E0),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Text(
                _error!,
                style: const TextStyle(color: Color(0xFF7A3E00)),
              ),
            ),
          ),
        ],
      ],
    );
  }
}
