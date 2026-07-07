import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Riverpod provider/state for AI symptom triage.
///
/// It polls `GET /api/v1/triage/{session}/result` until completed.
///
/// API base URL must be configured in your app.
class TriageProvider extends StateNotifier<TriageState> {
  TriageProvider({
    required Dio dio,
    required this.apiBaseUrl,
  })  : _dio = dio,
        super(TriageInitial());

  final Dio _dio;
  final String apiBaseUrl;

  String? _sessionId;

  Timer? _pollTimer;
  int _pollAttempts = 0;

  static const _pollInterval = Duration(seconds: 2);
  static const _maxPollAttempts = 60; // ~2 minutes

  /// Starts a new triage session and begins polling.
  Future<void> start({required String appointmentId}) async {
    _cancelPolling();

    state = TriageChatLoading();

    try {
      final res = await _dio.post<Map<String, dynamic>>(
        '$apiBaseUrl/api/v1/triage/start',
        data: <String, dynamic>{'appointment_id': appointmentId},
        options: Options(contentType: 'application/json'),
      );

      final payload = res.data;
      final success = payload?['success'] == true;

      if (!success) {
        throw DioException(requestOptions: res.requestOptions, error: payload?['message'] ?? 'Failed to start triage');
      }

      final sessionId = (payload?['data']?['session_id'] as String?) ?? '';
      if (sessionId.isEmpty) {
        throw StateError('Missing session_id in response.');
      }

      _sessionId = sessionId;
      state = TriageMessageReceived(
        sessionId: sessionId,
        completed: false,
        extractedSymptoms: const [],
        triageResult: null,
      );

      _startPolling();
    } catch (e) {
      state = TriageChatLoadingFailure(error: e);
    }
  }

  /// Submits a patient message to the triage session.
  Future<void> sendMessage(String message) async {
    final sessionId = _sessionId;
    if (sessionId == null || sessionId.isEmpty) {
      throw StateError('Triage session has not been started yet.');
    }

    state = TriageMessageSending();

    try {
      final res = await _dio.post<Map<String, dynamic>>(
        '$apiBaseUrl/api/v1/triage/$sessionId/message',
        data: <String, dynamic>{'message': message},
        options: Options(contentType: 'application/json'),
      );

      final payload = res.data;
      final success = payload?['success'] == true;

      if (!success) {
        throw DioException(requestOptions: res.requestOptions, error: payload?['message'] ?? 'Failed to send message');
      }

      state = TriageMessageReceived(
        sessionId: sessionId,
        completed: false,
        extractedSymptoms: const [],
        triageResult: (state is TriageCompleted) ? (state as TriageCompleted).triageResult : null,
      );

      _startPolling();
    } catch (e) {
      state = TriageChatLoadingFailure(error: e);
    }
  }

  /// Forces a one-shot poll (useful for UI refresh).
  Future<void> pollOnce() async {
    final sessionId = _sessionId;
    if (sessionId == null || sessionId.isEmpty) return;

    try {
      final res = await _dio.get<Map<String, dynamic>>(
        '$apiBaseUrl/api/v1/triage/$sessionId/result',
        queryParameters: const {},
      );

      final payload = res.data;
      final success = payload?['success'] == true;
      if (!success) {
        throw StateError(payload?['message'] ?? 'Failed to poll result');
      }

      final data = payload?['data'] as Map<String, dynamic>? ?? {};
      final completed = data['completed'] == true;
      final extractedSymptoms = (data['extracted_symptoms'] as List<dynamic>? ?? const [])
          .map((e) => e.toString())
          .toList();

      final triageResultRaw = data['triage_result'] as Map<String, dynamic>?;

      if (completed) {
        state = TriageCompleted(
          sessionId: sessionId,
          completed: true,
          extractedSymptoms: extractedSymptoms,
          triageResult: triageResultRaw == null ? null : TriageResult.fromMap(triageResultRaw),
        );
        _cancelPolling();
        return;
      }

      state = TriageMessageReceived(
        sessionId: sessionId,
        completed: false,
        extractedSymptoms: extractedSymptoms,
        triageResult: triageResultRaw == null ? null : TriageResult.fromMap(triageResultRaw),
      );
    } catch (e) {
      // Keep existing state; optionally you can expose an error state.
    }
  }

  void _startPolling() {
    if (_sessionId == null || _sessionId!.isEmpty) return;

    _pollTimer?.cancel();
    _pollAttempts = 0;

    _pollTimer = Timer.periodic(_pollInterval, (_) async {
      _pollAttempts++;
      await pollOnce();

      final s = state;
      final isDone = s is TriageCompleted;
      if (isDone || _pollAttempts >= _maxPollAttempts) {
        _cancelPolling();
      }
    });
  }

  void _cancelPolling() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  @override
  void dispose() {
    _cancelPolling();
    super.dispose();
  }
}

/// Base Riverpod state for Triage.
sealed class TriageState {}

class TriageInitial extends TriageState {}

class TriageChatLoading extends TriageState {}

class TriageMessageSending extends TriageState {}

class TriageChatLoadingFailure extends TriageState {
  TriageChatLoadingFailure({required this.error});
  final Object error;
}

class TriageMessageReceived extends TriageState {
  TriageMessageReceived({
    required this.sessionId,
    required this.completed,
    required this.extractedSymptoms,
    required this.triageResult,
  });

  final String sessionId;
  final bool completed;
  final List<String> extractedSymptoms;
  final TriageResult? triageResult;
}

class TriageCompleted extends TriageState {
  TriageCompleted({
    required this.sessionId,
    required this.completed,
    required this.extractedSymptoms,
    required this.triageResult,
  });

  final String sessionId;
  final bool completed;
  final List<String> extractedSymptoms;
  final TriageResult? triageResult;
}

/// Parsed triage result DTO expected from backend.
class TriageResult {
  TriageResult({
    required this.urgencyScore,
    required this.recommendedSpecialty,
    required this.extractedSymptoms,
    required this.redFlags,
  });

  final int urgencyScore;
  final String recommendedSpecialty;
  final List<String> extractedSymptoms;
  final List<String> redFlags;

  factory TriageResult.fromMap(Map<String, dynamic> map) {
    return TriageResult(
      urgencyScore: (map['urgency_score'] as num?)?.toInt() ?? 0,
      recommendedSpecialty: (map['recommended_specialty'] as String?) ?? '',
      extractedSymptoms: (map['extracted_symptoms'] as List<dynamic>? ?? const [])
          .map((e) => e.toString())
          .toList(),
      redFlags: (map['red_flags'] as List<dynamic>? ?? const [])
          .map((e) => e.toString())
          .toList(),
    );
  }
}

/// Riverpod provider factory.
///
/// Usage:
/// - Create a Dio instance with auth headers/interceptors in your app.
/// - Provide [apiBaseUrl] (e.g. https://api.yourdomain.com).
final triageProvider = StateNotifierProvider.autoDispose
    .family<TriageProvider, TriageState, ({Dio dio, String apiBaseUrl})>(
  (ref, input) {
    return TriageProvider(dio: input.dio, apiBaseUrl: input.apiBaseUrl);
  },
);

