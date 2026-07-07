import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../../packages/smartclinic_core/lib/providers/triage_provider.dart';

/// High-fidelity AI triage chat screen.
///
/// Expects a [TriageProvider] to be created by the parent with a Dio instance and apiBaseUrl.
class AiTriageChatScreen extends ConsumerStatefulWidget {
  const AiTriageChatScreen({
    super.key,
    required this.appointmentId,
    required this.dio,
    required this.apiBaseUrl,
  });

  final String appointmentId;
  final dynamic dio; // Keep signature flexible for your Dio import handling.
  final String apiBaseUrl;

  @override
  ConsumerState<AiTriageChatScreen> createState() => _AiTriageChatScreenState();
}

class _AiTriageChatScreenState extends ConsumerState<AiTriageChatScreen> {
  final TextEditingController _controller = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  bool _typing = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = ref.read(
        triageProvider((dio: widget.dio, apiBaseUrl: widget.apiBaseUrl)).notifier,
      );
      // Start the triage session when screen opens.
      provider.start(appointmentId: widget.appointmentId);
      setState(() {});
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Color _urgencyColor(int score) {
    // 1-5 => green -> amber -> red
    if (score <= 2) return Colors.green;
    if (score == 3) return Colors.orange;
    return Colors.red;
  }

  String _urgencyLabel(int score) {
    if (score <= 2) return 'Low risk';
    if (score == 3) return 'Moderate risk';
    return 'High urgency';
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(
      triageProvider((dio: widget.dio, apiBaseUrl: widget.apiBaseUrl)),
    );

    final triageResult = (state is TriageCompleted)
        ? state.triageResult
        : (state is TriageMessageReceived)
            ? state.triageResult
            : null;

    final urgencyScore = triageResult?.urgencyScore ?? 0;

    _typing = state is TriageMessageSending || state is TriageChatLoading;

    return Scaffold(
      appBar: AppBar(
        title: const Text('AI Symptom Triage'),
        backgroundColor: triageResult == null ? Colors.blueGrey : _urgencyColor(urgencyScore),
      ),
      body: Column(
        children: [
          _buildUrgencyHeader(triageResult),
          Expanded(
            child: _buildMessagesPlaceholder(state),
          ),
          _buildComposer(),
          if (_typing) _buildTypingIndicator(),
        ],
      ),
    );
  }

  Widget _buildUrgencyHeader(TriageResult? triageResult) {
    if (triageResult == null) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        color: Colors.blueGrey.withOpacity(0.08),
        child: const Text(
          'Answer a few quick questions. Your triage score will appear when complete.',
          style: TextStyle(fontSize: 13, color: Colors.black54),
        ),
      );
    }

    final color = _urgencyColor(triageResult.urgencyScore);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      color: color.withOpacity(0.12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              'Urgency ${triageResult.urgencyScore}/5',
              style: const TextStyle(fontWeight: FontWeight.w700, color: Colors.white),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              '${_urgencyLabel(triageResult.urgencyScore)} • Recommended: ${triageResult.recommendedSpecialty}',
              style: const TextStyle(fontSize: 13, color: Colors.black54),
            ),
          ),
        ],
      ),
    );
  }

  /// Placeholder message UI.
  ///
  /// Backend currently stores messages inside `ai_triage_sessions.messages`.
  /// This screen uses polling state only; wiring full message list requires
  /// either: (1) extending provider to fetch messages, or (2) streaming via websocket.
  Widget _buildMessagesPlaceholder(TriageState state) {
    // Minimal viable UI per blueprint (bubbles + typing indicator).
    // If you later expose message history through API, we can replace this
    // with a real ListView of bubbles.
    return ListView(
      controller: _scrollController,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      children: [
        _bubble(
          isPatient: false,
          text: 'Hi! I’m the AI triage nurse. I’ll ask a few questions to understand your symptoms.',
        ),
        if (state is TriageMessageReceived) ...[
          _bubble(isPatient: true, text: 'Ready. Let’s start.'),
        ],
      ],
    );
  }

  Widget _bubble({required bool isPatient, required String text}) {
    final color = isPatient ? Theme.of(context).colorScheme.primary : Colors.grey.shade300;
    final textColor = isPatient ? Colors.white : Colors.black87;

    return Align(
      alignment: isPatient ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 6),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        constraints: const BoxConstraints(maxWidth: 320),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(isPatient ? 16 : 0),
            bottomRight: Radius.circular(isPatient ? 0 : 16),
          ),
        ),
        child: Text(
          text,
          style: TextStyle(color: textColor, fontSize: 14),
        ),
      ),
    );
  }

  Widget _buildComposer() {
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: _controller,
                minLines: 1,
                maxLines: 4,
                decoration: InputDecoration(
                  hintText: 'Describe your symptoms...',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                ),
                onChanged: (_) => setState(() {}),
              ),
            ),
            const SizedBox(width: 10),
            SizedBox(
              width: 48,
              height: 48,
              child: IconButton(
                icon: const Icon(Icons.send, size: 20),
                onPressed: () async {
                  final text = _controller.text.trim();
                  if (text.isEmpty) return;
                  _controller.clear();

                  final notifier = ref.read(
                    triageProvider((dio: widget.dio, apiBaseUrl: widget.apiBaseUrl)).notifier,
                  );

                  await notifier.sendMessage(text);
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTypingIndicator() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12, left: 16, right: 16),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(16),
              ),
              child: const _ThreeDotsBounce(),
            ),
          ],
        ),
      ),
    );
  }
}

class _ThreeDotsBounce extends StatefulWidget {
  const _ThreeDotsBounce();

  @override
  State<_ThreeDotsBounce> createState() => _ThreeDotsBounceState();
}

class _ThreeDotsBounceState extends State<_ThreeDotsBounce> with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 900),
  )..repeat();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, _) {
        double t = _controller.value;
        return Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            _dot(t, 0.0),
            const SizedBox(width: 6),
            _dot(t, 0.33),
            const SizedBox(width: 6),
            _dot(t, 0.66),
          ],
        );
      },
    );
  }

  Widget _dot(double t, double phase) {
    // Simple bounce
    final local = (t + phase) % 1.0;
    final height = local < 0.5 ? local * 10 : (1 - local) * 10;
    return Transform.translate(
      offset: Offset(0, -height),
      child: const SizedBox(
        width: 8,
        height: 8,
        child: DecoratedBox(
          decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.black54),
        ),
      ),
    );
  }
}

