/**
 * Configuration frontend Master Money (Vercel).
 * Définir l'URL du backend Railway (variable d'environnement Vercel ou valeur par défaut).
 */
window.API_BASE_URL = typeof __MM_API_URL__ !== 'undefined'
    ? __MM_API_URL__
    : (window.API_BASE_URL || 'https://votre-app.railway.app');
