import 'dart:typed_data';
//import 'dart:io';
import 'analysis_screen.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../models/diagnosis_result.dart';
import 'dart:convert';
import '../main.dart';
import '../theme/app_theme.dart';
import 'result_screen.dart';

class AnalysisProgressScreen extends StatefulWidget {
  const AnalysisProgressScreen({
    required this.image,
    required this.previewBytes,
    super.key,
  });

  final XFile image;
  final Uint8List? previewBytes;

  @override
  State<AnalysisProgressScreen> createState() => _AnalysisProgressScreenState();
}

class _AnalysisProgressScreenState extends State<AnalysisProgressScreen> {
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _runAnalysis());
  }

  Future<void> _runAnalysis() async {
    setState(() => _error = null);
    try {
      //final File fileToAnalyze = File(widget.image.path);

      // 1. On récupère la réponse texte de Gemini
      final String? resultText = await analyserMaladieAvecGemini(widget.image);

      if (!mounted) return;

      if (resultText == null || resultText.startsWith("Erreur")) {
        setState(() => _error = resultText ?? "Une erreur inconnue est survenue.");
        return;
      }
      print("Réponse Gemini :");
      print(resultText);

      String clean = resultText.trim();

      if (clean.startsWith("```json")) {
        clean = clean.replaceFirst("```json", "");
      }

      if (clean.endsWith("```")) {
        clean = clean.substring(0, clean.length - 3);
      }


      final Map<String, dynamic> diagnosisjson = jsonDecode(clean) as Map<String, dynamic>;
      if (diagnosisjson["isPlant"] == false) {
        setState(() {
          _error = diagnosisjson["message"];
        });
        return;
      }

      diagnosisjson["imagePath"] = widget.image.path;
      diagnosisjson ["imageName"] = widget.image.name;

      final result = DiagnosisResult.fromJson(diagnosisjson );
      await AgricheckScope.of(context).addHistory(result);
      debugPrint("Historique : ${AgricheckScope.of(context).history.length}");
      // 3. On envoie l'objet à l'écran de résultat
      Navigator.of(context).pushReplacement(
        MaterialPageRoute<void>(
          builder: (_) => ResultScreen(result: result),
        ),
      );
    } catch (error) {
      if (mounted) {
        setState(() => _error = error.toString());
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Analyse en cours')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: <Widget>[
          if (widget.previewBytes != null)
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: SizedBox(
                height: 220,
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.memory(widget.previewBytes!,
                    fit: BoxFit.cover,
                  ),
                ),
              )
            ),
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    'Diagnostic Agricheck',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 12),
                  if (_error == null) ...<Widget>[
                    const LinearProgressIndicator(),
                    const SizedBox(height: 12),
                    const _StepRow(text: 'Identification de la plante'),
                    const _StepRow(text: 'Analyse de l image'),
                    const _StepRow(text: 'Detection de maladie'),
                    const _StepRow(text: 'Calcul de confiance'),
                    const _StepRow(text: 'Generation du diagnostic'),
                  ] else ...<Widget>[
                    Text(_error!, style: const TextStyle(color: Colors.red)),
                    const SizedBox(height: 12),
                    FilledButton.icon(
                      onPressed: _runAnalysis,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Reessayer'),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StepRow extends StatelessWidget {
  const _StepRow({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Row(
        children: <Widget>[
          const Icon(
            Icons.check_circle_outline,
            color: AppTheme.leaf,
            size: 20,
          ),
          const SizedBox(width: 8),
          Expanded(child: Text(text)),
        ],
      ),
    );
  }
}
