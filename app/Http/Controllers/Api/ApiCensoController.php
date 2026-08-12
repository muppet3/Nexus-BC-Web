import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static Future<String> _getBaseUrl() async {
    final prefs = await SharedPreferences.getInstance();
    final ip = prefs.getString('server_ip') ?? '192.168.1.100'; 
    return 'http://$ip/api';
  }

  static Future<Map<String, String>> _getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token') ?? '';
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token.isNotEmpty) 'Authorization': 'Bearer $token',
    };
  }

  // LOGIN
  static Future<Map<String, dynamic>> login(String username, String pin) async {
    try {
      final baseUrl = await _getBaseUrl();
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'username': username, 'pin': pin}),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Error de conexión: Verifica la IP del servidor.'};
    }
  }

  // BUSCADOR INTELIGENTE
  static Future<Map<String, dynamic>> buscarProducto(String query) async {
    try {
      final baseUrl = await _getBaseUrl();
      final headers = await _getHeaders();
      final response = await http.get(Uri.parse('$baseUrl/buscar?q=$query'), headers: headers);

      if (response.statusCode == 200) return jsonDecode(response.body);
      return {'success': false, 'data': []};
    } catch (e) {
      return {'success': false, 'message': 'Error al buscar: $e'};
    }
  }

  // GUARDAR CENSO
  static Future<Map<String, dynamic>> guardarCenso({
    required int productoId,
    required int cantidad,
    required String seccion,
    required String muebleTipo,
    required String muebleNumero,
    required String entrepano,
    required String marca, 
    required String codigoBarras, 
    String? supervisorUsername,
    String? supervisorPin,
    int? hallazgoId, 
  }) async {
    try {
      final baseUrl = await _getBaseUrl();
      final headers = await _getHeaders();
      
      final body = {
        'producto_id': productoId,
        'cantidad': cantidad,
        'seccion': seccion,
        'mueble_tipo': muebleTipo,
        'mueble_numero': muebleNumero,
        'entrepano': entrepano,
        'marca': marca,
        'codigo_barras': codigoBarras,
      };

      if (hallazgoId != null) body['hallazgo_id'] = hallazgoId;
      if (supervisorUsername != null && supervisorPin != null) {
        body['supervisor_username'] = supervisorUsername;
        body['supervisor_pin'] = supervisorPin;
      }

      final response = await http.post(
        Uri.parse('$baseUrl/guardar-hallazgo'),
        headers: headers,
        body: jsonEncode(body),
      );

      final decodedData = jsonDecode(response.body);
      if (response.statusCode == 403 && decodedData['needs_auth'] == true) {
        return decodedData;
      }
      return decodedData;
    } catch (e) {
      return {'success': false, 'message': 'Error al guardar: $e'};
    }
  }

  // MI HISTORIAL DEL DÍA
  static Future<Map<String, dynamic>> miHistorial() async {
    try {
      final baseUrl = await _getBaseUrl();
      final headers = await _getHeaders();
      final response = await http.get(Uri.parse('$baseUrl/mi-historial'), headers: headers);
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Error al cargar historial: $e'};
    }
  }

  // OBTENER HISTORIAL DE AUDITORÍA
  static Future<Map<String, dynamic>> historialProducto(int id) async {
    try {
      final baseUrl = await _getBaseUrl();
      final headers = await _getHeaders();
      final response = await http.get(Uri.parse('$baseUrl/historial-producto/$id'), headers: headers);

      if (response.statusCode == 200) return jsonDecode(response.body);
      return {'success': false, 'auditorias': [], 'hallazgos': []};
    } catch (e) {
      return {'success': false, 'message': 'Error al cargar auditoría: $e'};
    }
  }

  // 🔥 CORRECCIÓN: IMPRESIÓN ZEBRA (Ahora recibe el código a imprimir)
  static Future<Map<String, dynamic>> imprimirEtiqueta(int productoId, String codigoAImprimir) async {
    try {
      final baseUrl = await _getBaseUrl();
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/imprimir-etiqueta'),
        headers: headers,
        body: jsonEncode({
            'producto_id': productoId,
            'codigo_impreso': codigoAImprimir
        }),
      );
      return jsonDecode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Error de conexión con la impresora: $e'};
    }
  }
}