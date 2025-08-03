import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || '';

const queueTypeService = {
  // Pobierz wszystkie typy kolejek
  getAll: async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/queue-types`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to fetch queue types');
    }
  },

  // Pobierz pojedynczy typ kolejki
  getById: async (id) => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/queue-types/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to fetch queue type');
    }
  },

  // Utwórz nowy typ kolejki
  create: async (queueTypeData) => {
    try {
      const response = await axios.post(`${API_BASE_URL}/api/queue-types`, queueTypeData);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to create queue type');
    }
  },

  // Zaktualizuj typ kolejki
  update: async (id, queueTypeData) => {
    try {
      const response = await axios.put(`${API_BASE_URL}/api/queue-types/${id}`, queueTypeData);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to update queue type');
    }
  }
};

export default queueTypeService;