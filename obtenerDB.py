from Bio import Entrez, Medline
import pandas as pd
import time

# Tu correo es obligatorio para usar Entrez
Entrez.email = "sauljesus_12@hotmail.com"

# Términos médicos comunes que puedes cambiar o ampliar
temas = ["cancer", "diabetes", "asma", "hipertension", "obesidad", "enfermedades cardiovasculares"]

# Lista para almacenar todos los resultados
datos = []

for tema in temas:
    try:
        print(f"Buscando artículos sobre: {tema}")
        # Buscar hasta 500 artículos por tema
        search = Entrez.esearch(db="pubmed", term=f"{tema}[Title/Abstract] AND spanish[Language]", retmax=500)
        search_results = Entrez.read(search)
        id_list = search_results["IdList"]

        # Obtener los artículos con efetch
        fetch = Entrez.efetch(db="pubmed", id=id_list, rettype="medline", retmode="text")
        records = Medline.parse(fetch)

        for record in records:
            title = record.get("TI", "")
            abstract = record.get("AB", "")
            if abstract:  # Solo guardar si hay abstract
                datos.append({
                    "Tema": tema,
                    "ID": record.get("PMID", ""),
                    "Titulo": title,
                    "Texto": abstract
                })
        time.sleep(1)  # Espera para no saturar la API
    except Exception as e:
        print(f"Error con el tema {tema}: {str(e)}")

# Guardar en CSV
df = pd.DataFrame(datos)
df.to_csv("pubmed_dataset.csv", index=False, encoding='utf-8')

print(f"Se guardaron {len(datos)} registros en pubmed_dataset.csv")
