import pandas as pd
import joblib
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.pipeline import make_pipeline

#Solo usar esta clase para entrenar en google colab

# Cargar datasets
df_fake = pd.read_csv("onlyfakes1000.csv")  # Noticias falsas
df_true = pd.read_csv("onlytrue1000.csv")  # Noticias reales

# Agregar la columna 'label' (0 = fake, 1 = real)
df_fake["label"] = 0
df_true["label"] = 1

# Unir ambos datasets
df = pd.concat([df_fake, df_true], ignore_index=True)

# Seleccionar características (X) y etiquetas (y)
X = df["text"]  # Solo el texto de la noticia
y = df["label"]  # Etiqueta fake=0, real=1

# Crear modelo Naive Bayes con TF-IDF
modelo = make_pipeline(TfidfVectorizer(), MultinomialNB())

# Entrenar el modelo
modelo.fit(X, y)

# Guardar modelo entrenado
joblib.dump(modelo, "modelo_fake_news.pkl")
print("Modelo entrenado y guardado exitosamente.")

