import pandas as pd
from transformers import pipeline
from tqdm import tqdm

df = pd.read_csv("pubmed_dataset.csv")

# Verificar que existan las columnas necesarias
required_columns = ["Titulo", "Texto"]
for col in required_columns:
    if col not in df.columns:
        raise ValueError(f"La columna '{col}' no se encuentra en el DataFrame.")

# Inicializar el modelo de traducción
translator = pipeline("translation_en_to_es", model="Helsinki-NLP/opus-mt-en-es")

# Función para traducir en lotes
def translate_batch(texts, max_length=512):
    try:
        # Limitar longitud y evitar None
        texts = [str(t)[:max_length] if pd.notna(t) else "" for t in texts]
        translations = translator(texts)
        return [t['translation_text'] for t in translations]
    except Exception as e:
        print(f"Error al traducir lote: {str(e)}")
        return texts  # Devolver los textos originales si falla

# Traducir columna 'Titulo'
titulo_traducido = []
print("Traduciendo títulos...")
for i in tqdm(range(0, len(df), 8)):
    batch = df["Titulo"].iloc[i:i+8].tolist()
    traducidos = translate_batch(batch)
    titulo_traducido.extend(traducidos)

# Traducir columna 'Texto'
texto_traducido = []
print("Traduciendo textos...")
for i in tqdm(range(0, len(df), 8)):
    batch = df["Texto"].iloc[i:i+8].tolist()
    traducidos = translate_batch(batch)
    texto_traducido.extend(traducidos)

# Agregar nuevas columnas al DataFrame
df["Titulo_Traducido"] = titulo_traducido
df["Texto_Traducido"] = texto_traducido

# Guardar archivo
df.to_csv("dataset_traducido.csv", index=False, encoding="utf-8-sig")
print("✅ Traducción completada. Archivo guardado como 'dataset_traducido.csv'.")
