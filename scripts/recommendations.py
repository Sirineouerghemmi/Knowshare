import mysql.connector
import json
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import numpy as np
from datetime import datetime, timedelta
import logging

# Set up logging
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s - %(levelname)s - %(message)s',
    filename='C:/xampp/htdocs/knowshare/logs/recommendations.log'
)

def connect_to_db():
    try:
        conn = mysql.connector.connect(
            host="127.0.0.1",
            user="root",
            password="",
            database="knowshare"
        )
        logging.debug("Database connection successful")
        return conn
    except mysql.connector.Error as e:
        logging.error(f"Database connection error: {e}")
        return None

def get_user_search_history(conn, user_id, days=30):
    cursor = conn.cursor(dictionary=True)
    try:
        date_limit = datetime.now() - timedelta(days=days)
        query = """
            SELECT search_term
            FROM search_history
            WHERE user_id = %s AND created_at >= %s
            ORDER BY created_at DESC
            LIMIT 10
        """
        cursor.execute(query, (user_id, date_limit))
        search_terms = [row['search_term'] for row in cursor.fetchall()]
        logging.debug(f"Search history for user {user_id}: {search_terms}")
        return search_terms
    except mysql.connector.Error as e:
        logging.error(f"Error fetching search history for user {user_id}: {e}")
        return []
    finally:
        cursor.close()

def get_all_documents(conn):
    cursor = conn.cursor(dictionary=True)
    try:
        query = """
            SELECT d.id, d.title, d.path, c.nom AS category_name
            FROM document d
            JOIN category c ON d.id_category = c.id
            WHERE d.title IS NOT NULL AND d.title != ''
            ORDER BY d.date DESC
        """
        cursor.execute(query)
        documents = cursor.fetchall()
        logging.debug(f"Retrieved {len(documents)} documents: {[doc['title'] for doc in documents]}")
        return documents
    except mysql.connector.Error as e:
        logging.error(f"Error fetching documents: {e}")
        return []
    finally:
        cursor.close()

def generate_recommendations(user_id):
    logging.debug(f"Starting recommendations for user {user_id}")
    conn = connect_to_db()
    if not conn:
        logging.error("Database connection failed")
        return {"recommendations": [], "message": "Database connection failed"}

    try:
        # Get user search history
        search_terms = get_user_search_history(conn, user_id)
        logging.debug(f"Search terms: {search_terms}")

        # Get all documents
        documents = get_all_documents(conn)
        if not documents:
            logging.warning("No documents available")
            return {"recommendations": [], "message": "No documents available"}

        # If no search terms, return recent documents
        if not search_terms:
            logging.debug("No search terms, returning recent documents")
            recommendations = [
                {
                    "id": doc['id'],
                    "title": doc['title'],
                    "path": doc['path'],
                    "category": doc['category_name'],
                    "score": 0.0
                } for doc in documents[:5]
            ]
            return {"recommendations": recommendations, "message": "No search history, showing recent documents"}

        # Prepare texts for TF-IDF
        doc_texts = [f"{doc['title']} {doc['category_name']}" for doc in documents]
        search_text = " ".join(search_terms)
        logging.debug(f"Document texts: {doc_texts[:2]}")
        logging.debug(f"Search text: {search_text}")

        # Vectorize texts
        vectorizer = TfidfVectorizer(stop_words='english')
        try:
            tfidf_matrix = vectorizer.fit_transform(doc_texts + [search_text])
        except ValueError as e:
            logging.error(f"TF-IDF vectorization failed: {e}")
            recommendations = [
                {
                    "id": doc['id'],
                    "title": doc['title'],
                    "path": doc['path'],
                    "category": doc['category_name'],
                    "score": 0.0
                } for doc in documents[:5]
            ]
            return {"recommendations": recommendations, "message": "TF-IDF failed, showing recent documents"}

        # Compute cosine similarity
        doc_vectors = tfidf_matrix[:-1]
        search_vector = tfidf_matrix[-1]
        similarities = cosine_similarity(search_vector, doc_vectors).flatten()
        logging.debug(f"Similarity scores: {similarities.tolist()}")

        # Get top 5 documents
        top_indices = np.argsort(similarities)[::-1][:5]
        recommendations = [
            {
                "id": documents[i]['id'],
                "title": documents[i]['title'],
                "path": documents[i]['path'],
                "category": documents[i]['category_name'],
                "score": float(similarities[i])
            }
            for i in top_indices
        ]
        logging.debug(f"Recommendations for user {user_id}: {recommendations}")
        return {"recommendations": recommendations}

    except Exception as e:
        logging.error(f"Unexpected error: {e}")
        return {"recommendations": [], "message": f"Error: {str(e)}"}
    finally:
        if conn:
            conn.close()

def main():
    import sys
    if len(sys.argv) != 2:
        logging.error("User ID required")
        print(json.dumps({"error": "User ID required"}))
        return
    try:
        user_id = int(sys.argv[1])
        result = generate_recommendations(user_id)
        print(json.dumps(result))
    except ValueError:
        logging.error("Invalid user ID")
        print(json.dumps({"error": "Invalid user ID"}))

if __name__ == "__main__":
    main()