# -*- coding: utf-8 -*- # Añadir esto por si acaso hay problemas de encoding
import requests
from bs4 import BeautifulSoup
import pandas as pd
import time
from urllib.parse import quote, urljoin, urlparse
from fake_useragent import UserAgent
import random
import logging

# Configuración de logging (cambiar a DEBUG para ver más detalles)
logging.basicConfig(level=logging.DEBUG, format='%(asctime)s - %(levelname)s - %(message)s')

# Configuración inicial
try:
    ua = UserAgent()
except Exception:
    logging.warning("No se pudo inicializar fake_useragent. Usando un User-Agent genérico.")
    ua = type('obj', (object,), {'random': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'})()

DEFAULT_MAX_ARTICULOS = 5
temas = ["cancer"]

# --- SELECTORES ACTUALIZADOS (Mayo 2025 - ¡Revisar si fallan!) ---
fuentes_confiables = [
    {
        'nombre': 'maldita.es',
        'url_base': 'https://maldita.es',
        'url_busqueda_pattern': 'https://maldita.es/buscar/{query}',
        'query_param': None,
        'selectors': {
            # Actualizado: Parece que ahora usan 'search-results-list__item' o similar dentro de un div
            # Probemos un selector más específico si 'article' falla.
            # Intentemos con el contenedor que parece envolver los resultados
            'articles': ('div.search-results-list > article', {}), # Intento más específico
            'title': ('h2 > a', {}),
            'summary': ('div.c-card__excerpt', {}), # Mantener este por ahora
            'link': ('h2 > a', {})
        },
        'funcion': 'standard'
    },
    {
        'nombre': 'aarp_espanol',
        'url_base': 'https://www.aarp.org',
        'url_busqueda_pattern': 'https://www.aarp.org/espanol/busqueda/?search={query}',
        'query_param': 'search',
        'selectors': {
             # Mantener el selector original que parecía correcto, pero verificar si sigue vigente.
             # Si falla, inspeccionar de nuevo. Podría ser algo como div.search-results > div.search-results-item
            'articles': ('div', {'class': 'search-results-item'}),
            'title': ('h2 > a', {}),
            'summary': ('div', {'class': 'search-results-description'}),
            'link': ('h2 > a', {})
        },
        'funcion': 'standard'
    },
    {
        'nombre': 'saludsinbulos',
        'url_base': 'https://saludsinbulos.com',
        'url_busqueda_pattern': 'https://saludsinbulos.com/?s={query}',
        'query_param': 's',
        'selectors': {
            'articles': ('article', {'class': 'post'}), # Este funcionaba para encontrar artículos
            'title': ('h2.entry-title > a', {}),
            'summary': ('div.entry-summary', {}), # Quitar el 'p' para capturar todo el div
            'link': ('h2.entry-title > a', {})
        },
        'funcion': 'standard'
    },
    {
        'nombre': 'newtral_verifica',
        'url_base': 'https://www.newtral.es',
        # CORREGIDO: URL de búsqueda correcta para la sección verificación
        'url_busqueda_pattern': 'https://www.newtral.es/?s={query}',
        'query_param': 's',
        'selectors': {
            # Mantener 'article.c-card', parece estándar en Newtral
            'articles': ('article', {'class': 'c-card'}),
            'title': ('h2.c-card__title > a', {}),
            'summary': ('p', {'class': 'c-card__excerpt'}),
            'link': ('h2.c-card__title > a', {})
        },
        'funcion': 'standard'
    },
    {
        'nombre': 'efe_verifica',
        'url_base': 'https://verifica.efe.com',
        'url_busqueda_pattern': 'https://verifica.efe.com/?s={query}',
        'query_param': 's',
        'selectors': {
            # Actualizado: Selector más específico para Elementor
             'articles': ('div.elementor-posts-container article.elementor-post', {}),
            'title': ('h3.elementor-post__title > a', {}),
            'summary': ('div.elementor-post__excerpt', {}),
            'link': ('h3.elementor-post__title > a', {})
        },
        'funcion': 'standard'
    },
]

datos_noticias = []

def scrape_standard(tema, fuente, max_articulos=DEFAULT_MAX_ARTICULOS):
    headers = {'User-Agent': ua.random}
    try:
        url_busqueda = fuente['url_busqueda_pattern'].format(query=quote(tema))
        logging.info(f"Buscando '{tema}' en {fuente['nombre']} (Máx {max_articulos} artículos)")
        logging.debug(f"URL: {url_busqueda}")

        response = requests.get(url_busqueda, headers=headers, timeout=20)
        # Añadir log para ver el status code antes de que falle raise_for_status
        logging.debug(f"Status Code para {fuente['nombre']} ({tema}): {response.status_code}")
        response.raise_for_status()
        response.encoding = response.apparent_encoding
        soup = BeautifulSoup(response.text, 'html.parser')

        articles_selector_tag, articles_selector_attrs = fuente['selectors']['articles']
        title_selector_tag, title_selector_attrs = fuente['selectors']['title']
        summ_selector_tag, summ_selector_attrs = fuente['selectors']['summary']
        link_selector_tag, link_selector_attrs = fuente['selectors']['link']

        # Si es un selector CSS complejo, usar select() en lugar de find_all
        if isinstance(articles_selector_tag, str) and ('>' in articles_selector_tag or '.' in articles_selector_tag or '#' in articles_selector_tag):
             logging.debug(f"Usando select() para selector complejo: {articles_selector_tag}")
             articulos = soup.select(articles_selector_tag) # Usar select para selectores CSS complejos
             if articles_selector_attrs: # Si hay atributos, filtrar después (menos eficiente)
                 articulos = [art for art in articulos if all(art.get(k) == v for k, v in articles_selector_attrs.items())]
        else:
            articulos = soup.find_all(articles_selector_tag, attrs=articles_selector_attrs)

        logging.info(f"Artículos encontrados en {fuente['nombre']}: {len(articulos)}")
        # Debug: Si no encuentra artículos, mostrar un trozo del HTML recibido
        if not articulos and logging.getLogger().level == logging.DEBUG:
             logging.debug(f"HTML recibido de {fuente['nombre']} (primeros 2000 chars):\n{soup.prettify()[:2000]}")

        count = 0
        for i, articulo in enumerate(articulos): # Usar enumerate para index
            if count >= max_articulos:
                break

            try:
                titulo_elem = articulo.find(title_selector_tag, attrs=title_selector_attrs)
                titulo = titulo_elem.get_text(strip=True) if titulo_elem else "Sin título"

                enlace_elem = articulo.find(link_selector_tag, attrs=link_selector_attrs)
                enlace_rel = enlace_elem['href'] if enlace_elem and enlace_elem.has_attr('href') else ""
                enlace = ""
                if enlace_rel:
                    enlace = urljoin(fuente['url_base'], enlace_rel)

                resumen_elem = articulo.find(summ_selector_tag, attrs=summ_selector_attrs)
                resumen = resumen_elem.get_text(strip=True) if resumen_elem else ""

                # --- DEBUG ESPECÍFICO PARA SALUDSINBULOS ---
                if fuente['nombre'] == 'saludsinbulos':
                    logging.debug(f"[SaludSinBulos - Artículo {i}] Título extraído: '{titulo}'")
                    logging.debug(f"[SaludSinBulos - Artículo {i}] Resumen extraído: '{resumen[:100]}...'") # Mostrar inicio del resumen

                # --- Filtro Básico de Relevancia ---
                tema_lower = tema.lower()
                # Convertir espacios múltiples o caracteres especiales si es necesario en el futuro
                # tema_clean = ' '.join(tema_lower.split())

                # Comprobación más flexible (ignora espacios extra al inicio/fin)
                titulo_check = titulo.lower().strip()
                resumen_check = resumen.lower().strip()

                # DESCOMENTAR PARA DESACTIVAR FILTRO TEMPORALMENTE:
                # logging.debug(f"Filtro de relevancia DESACTIVADO para prueba.")
                # es_relevante = True

                # FILTRO NORMAL (COMENTADO TEMPORALMENTE):
                # es_relevante = tema_lower in titulo_check or tema_lower in resumen_check

                # if not es_relevante:
                #     logging.debug(f"Artículo descartado (Filtro Relevancia: '{tema_lower}' no en T:'{titulo_check}' ni R:'{resumen_check[:50]}...'): {titulo}")
                #     continue # Pasar al siguiente artículo si no parece relevante
                # else:
                #     logging.debug(f"Artículo ACEPTADO (Relevante): {titulo}") # Esto se logueará siempre ahora



                logging.debug(f"Artículo procesado: T:{titulo} | R:{resumen[:50]}... | L:{enlace}")

                datos_noticias.append({
                    'Tema': tema,
                    'Fuente': fuente['nombre'],
                    'Título': titulo,
                    'Resumen': resumen,
                    'Enlace': enlace,
                    'Fecha_Scraping': time.strftime("%Y-%m-%d %H:%M:%S")
                })
                count += 1

            except Exception as e:
                logging.error(f"Error procesando artículo {i} en {fuente['nombre']} para '{tema}': {e}", exc_info=True)
                continue

    except requests.exceptions.Timeout:
        logging.error(f"Timeout al conectar con {fuente['nombre']} para '{tema}' en {url_busqueda}")
    except requests.exceptions.RequestException as e:
        # No loguear error 404 como crítico si es esperado en búsquedas sin resultados
        if hasattr(e, 'response') and e.response is not None and e.response.status_code == 404:
             logging.warning(f"Error 404 (Not Found) para {fuente['nombre']} en {url_busqueda}. Puede que no haya resultados para '{tema}'.")
        else:
             logging.error(f"Error de conexión/HTTP con {fuente['nombre']} para '{tema}': {e}")
    except Exception as e:
        logging.error(f"Error inesperado haciendo scraping de {fuente['nombre']} para '{tema}': {e}", exc_info=True)

# --- El resto del código (main) permanece igual ---
def main():
    """
    Función principal para ejecutar el scraper.
    """
    logging.info("=== Scraper de Noticias Médicas Verificadas ===")
    logging.info(f"Temas a buscar: {', '.join(temas)}")
    logging.info(f"Fuentes configuradas: {', '.join([f['nombre'] for f in fuentes_confiables])}")

    try:
        # Poner un límite razonable por si acaso
        max_arts_input = input(f"\n¿Cuántos artículos relevantes deseas por fuente/tema? (Enter para {DEFAULT_MAX_ARTICULOS}): ")
        max_arts = min(int(max_arts_input) if max_arts_input else DEFAULT_MAX_ARTICULOS, 100) # Limitar a 100 máx
        logging.info(f"Se buscarán hasta {max_arts} artículos por fuente/tema.")
    except ValueError:
        logging.warning(f"Valor inválido. Usando valor por defecto: {DEFAULT_MAX_ARTICULOS}")
        max_arts = DEFAULT_MAX_ARTICULOS

    start_time = time.time()

    logging.info("\nIniciando búsqueda...")
    for tema in temas:
        logging.info(f"\n--- Buscando tema: '{tema}' ---")
        for fuente in fuentes_confiables:
            if fuente.get('funcion') == 'standard':
                 scrape_standard(tema, fuente, max_articulos=max_arts)
            else:
                 logging.warning(f"Función de scraping '{fuente.get('funcion')}' no implementada para {fuente['nombre']}")

            sleep_time = random.uniform(3, 7)
            logging.debug(f"Esperando {sleep_time:.2f} segundos...")
            time.sleep(sleep_time)

    if datos_noticias:
        df = pd.DataFrame(datos_noticias)
        df = df.drop_duplicates(subset=['Título', 'Fuente'], keep='first')
        df = df.sort_values(by=['Tema', 'Fuente'])

        timestamp = time.strftime("%Y%m%d-%H%M%S")
        csv_filename = f"noticias_medicas_verificadas_{timestamp}.csv"

        try:
            df.to_csv(csv_filename, index=False, encoding='utf-8-sig')
            end_time = time.time()
            logging.info(f"\n✅ Búsqueda completada en {end_time - start_time:.2f} segundos")
            # Usar len(df) para el conteo final de artículos únicos guardados
            logging.info(f"📄 Se guardaron {len(df)} noticias únicas relevantes.")
            logging.info("📂 Archivo generado:")
            logging.info(f"   - {csv_filename}")

        except Exception as e:
            logging.error(f"Error al guardar los resultados en archivo: {e}")

    else:
        end_time = time.time()
        logging.warning("\n⚠️ No se encontraron noticias relevantes que pasaran los filtros para los temas y fuentes especificados.")
        logging.info(f"\nBúsqueda completada (sin resultados guardados) en {end_time - start_time:.2f} segundos")


if __name__ == "__main__":
    main()