/**
 * Funkcje pomocnicze do formatowania dat
 * Wszystkie daty z API są w UTC, więc używamy metod UTC
 */

/**
 * Formatuje datę UTC do wyświetlenia w formacie DD.MM.YYYY, HH:MM:00
 * @param {string} dateString - Data w formacie ISO string (UTC)
 * @returns {string} - Sformatowana data
 */
export const formatDateUTC = (dateString) => {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  
  // Sprawdź czy data jest poprawna
  if (isNaN(date.getTime())) {
    return 'Nieprawidłowa data';
  }
  
  // Użyj UTC metod aby uniknąć konwersji strefy czasowej
  const year = date.getUTCFullYear();
  const month = String(date.getUTCMonth() + 1).padStart(2, '0');
  const day = String(date.getUTCDate()).padStart(2, '0');
  const hours = String(date.getUTCHours()).padStart(2, '0');
  const minutes = String(date.getUTCMinutes()).padStart(2, '0');
  
  return `${day}.${month}.${year}, ${hours}:${minutes}:00`;
};

/**
 * Formatuje datę UTC do wyświetlenia w formacie DD.MM.YYYY
 * @param {string} dateString - Data w formacie ISO string (UTC)
 * @returns {string} - Sformatowana data
 */
export const formatDateOnlyUTC = (dateString) => {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  
  if (isNaN(date.getTime())) {
    return 'Nieprawidłowa data';
  }
  
  const year = date.getUTCFullYear();
  const month = String(date.getUTCMonth() + 1).padStart(2, '0');
  const day = String(date.getUTCDate()).padStart(2, '0');
  
  return `${day}.${month}.${year}`;
};

/**
 * Formatuje datę UTC do wyświetlenia w formacie DD.MM.YYYY, HH:MM
 * @param {string} dateString - Data w formacie ISO string (UTC)
 * @returns {string} - Sformatowana data
 */
export const formatDateTimeUTC = (dateString) => {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  
  if (isNaN(date.getTime())) {
    return 'Nieprawidłowa data';
  }
  
  const year = date.getUTCFullYear();
  const month = String(date.getUTCMonth() + 1).padStart(2, '0');
  const day = String(date.getUTCDate()).padStart(2, '0');
  const hours = String(date.getUTCHours()).padStart(2, '0');
  const minutes = String(date.getUTCMinutes()).padStart(2, '0');
  
  return `${day}.${month}.${year}, ${hours}:${minutes}`;
};
