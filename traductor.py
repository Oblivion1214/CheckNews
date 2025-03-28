import pandas as pd
from transformers import pipeline
from tqdm import tqdm

# Cargar el dataset (ajusta el nombre del archivo y el delimitador si es necesario)
df = pd.read_csv("fake_news_dataset.csv")  # Asegúrate de cambiar el nombre del archivo si es necesario

# Inicializar el modelo de traducción
translator = pipeline("translation_en_to_es", model="Helsinki-NLP/opus-mt-en-es")

# Función para traducir el texto en lotes
def translate_text(text):
    if pd.isna(text) or text.strip() == "":
        return text  # Si el texto está vacío, lo dejamos igual
    try:
        translation = translator(text[:512])[0]['translation_text']  # Limitar a 512 caracteres para evitar errores
        return translation
    except Exception as e:
        print(f"Error traduciendo: {text[:50]}... - {str(e)}")
        return text  # Sí hay un error, mantener el texto original

# Aplicar la traducción a las columnas 'title' y 'text' usando tqdm para ver el progreso
tqdm.pandas()
df["title_es"] = df["title"].progress_apply(translate_text)
df["text_es"] = df["text"].progress_apply(translate_text)

# Guardar el dataset traducido
df.to_csv("dataset_traducido.csv", index=False, encoding="utf-8")

print("Traducción completada. Archivo guardado como 'dataset_traducido.csv'.")
