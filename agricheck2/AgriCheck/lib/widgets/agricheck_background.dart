import 'package:flutter/material.dart';

class AgricheckBackground extends StatelessWidget {
  const AgricheckBackground({
    required this.child,
    this.imageAsset = seedlingsImage,
    this.overlayOpacity = 0.52,
    this.alignment = Alignment.center,
    super.key,
  });

  static const String seedlingsImage =
      'assets/images/istockphoto-1501984364-612x612.jpg';
  static const String wheatImage =
      'assets/images/istockphoto-2221001557-612x612.webp';

  final Widget child;
  final String imageAsset;
  final double overlayOpacity;
  final Alignment alignment;

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: <Widget>[
        Image.asset(imageAsset, fit: BoxFit.cover, alignment: alignment),
        DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: <Color>[
                Colors.black.withValues(alpha: overlayOpacity * 0.82),
                Colors.black.withValues(alpha: overlayOpacity),
                Colors.black.withValues(alpha: overlayOpacity * 1.12),
              ],
            ),
          ),
        ),
        child,
      ],
    );
  }
}
