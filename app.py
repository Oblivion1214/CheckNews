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

# Endpoint para analizar un JSON
@app.route('/predict', methods=['POST'])
def predict():
    try:
        data = request.get_json()
        if not data:
            return jsonify({"error": "No se recibió un JSON válido"}), 400

        noticias = data.get("Noticias", [])
        if not isinstance(noticias, list):
            return jsonify({"error": "El campo 'Noticias' debe ser una lista"}), 400

        resultados = []
        for noticia in noticias:
            # Verificar si la noticia tiene una URL
            url = noticia.get("url", "")
            if url:  # Si hay URL, procesar como URL
                try:
                    # Procesar la URL con Newspaper3k
                    article = Article(url)
                    article.download()
                    article.parse()
                    text = article.text

                    if not text:
                        resultados.append({
                            "IdNoticia": noticia.get("IdNoticia"),
                            "error": "No se pudo extraer el texto de la URL"
                        })
                        continue

                    prediccion = modelo.predict([text])[0]
                    resultado = "Noticia falsa" if prediccion == 0 else "Noticia real"
                    resultados.append({
                        "IdNoticia": noticia.get("IdNoticia"),
                        "Titulo": noticia.get("Titulo", ""),
                        "url": url,
                        "texto_extraido": text[:200] + "..." if len(text) > 200 else text,
                        "prediccion": int(prediccion),
                        "mensaje": resultado
                    })
                except Exception as e:
                    print(f"Error al procesar la noticia con URL {url}: {str(e)}")
                    resultados.append({
                        "IdNoticia": noticia.get("IdNoticia"),
                        "Titulo": noticia.get("Titulo", ""),
                        "url": url,
                        "error": f"Error al procesar la URL: {str(e)}"
                    })
                continue  # Si procesamos la URL, no necesitamos seguir con el procesamiento de texto crudo

            # Si no hay URL, procesar el texto crudo
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
        print("Error en la ruta predict:", str(e))
        return jsonify({"error": f"Hubo un error en el servidor: {str(e)}"}), 500


# Ejecutar la API
if __name__ == '__main__':
    app.run(host="0.0.0.0", port=5000, debug=True)


# curl -X POST http://127.0.0.1:5000/predict -H "Content-Type: application/json" -d @"C:\Users\saulj\Documents\GitHub\CheckNews\noticiaurl.json"