import joblib
from flask import Flask, request, jsonify
from flask_cors import CORS
from newspaper import Article

# Crear la aplicación Flask
app = Flask(__name__)
CORS(app)

# Cargar el modelo entrenado
try:
    modelo = joblib.load("modelo_fake_news.pkl")
    print("Modelo cargado exitosamente")
except Exception as e:
    print(f"Error al cargar el modelo: {e}")
    modelo = None

# Endpoint para analizar una noticia desde un JSON (por ejemplo, para probar con contenido completo)
@app.route('/predict_json', methods=['POST'])
def predict_json():
    try:
        data = request.get_json()
        if not data:
            return jsonify({"error": "No se recibió un JSON válido"}), 400

        noticias = data.get("Noticias", [])
        if not isinstance(noticias, list):
            return jsonify({"error": "El campo 'Noticias' debe ser una lista"}), 400

        resultados = []
        for noticia in noticias:
            texto = noticia.get("Noticia", "")
            if texto:
                prediccion = modelo.predict([texto])[0]
                resultado = "Noticia falsa" if prediccion == 0 else "Noticia real"
                resultados.append({
                    "IdNoticia": noticia["IdNoticia"],
                    "Titulo": noticia["Titulo"],
                    "Noticia": texto,
                    "Prediccion": int(prediccion),
                    "Mensaje": resultado
                })
        return jsonify({"Resultados": resultados})
    except Exception as e:
        print("Error en la ruta predict_json:", str(e))
        return jsonify({"error": f"Hubo un error en el servidor: {str(e)}"}), 500

# Nuevo endpoint para analizar la noticia a partir de una URL
@app.route('/predict_url', methods=['POST'])
def predict_url():
    try:
        data = request.get_json()
        if not data:
            return jsonify({"error": "No se recibió un JSON válido"}), 400

        url = data.get("url", "")
        if not url:
            return jsonify({"error": "No se proporcionó una URL"}), 400

        # Extraer el contenido de la noticia usando Newspaper3k
        article = Article(url)
        article.download()
        article.parse()
        text = article.text

        if not text:
            return jsonify({"error": "No se pudo extraer el texto de la URL"}), 400

        # Predecir la veracidad usando el modelo
        prediccion = modelo.predict([text])[0]
        resultado = "Noticia falsa" if prediccion == 0 else "Noticia real"

        return jsonify({
            "url": url,
            "texto_extraido": text[:200] + "...",  # Opcional: mostrar un extracto
            "prediccion": int(prediccion),
            "mensaje": resultado
        })
    except Exception as e:
        print("Error en la ruta predict_url:", str(e))
        return jsonify({"error": f"Hubo un error en el servidor: {str(e)}"}), 500

# Ejecutar la API
if __name__ == '__main__':
    app.run(host="0.0.0.0", port=5000, debug=True)
