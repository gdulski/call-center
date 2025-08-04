import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || '';

const availabilityService = {
  // Pobierz dostępności dla agenta
  getByAgent: async (agentId) => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/availability?agentId=${agentId}`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to fetch availabilities');
    }
  },

  // Pobierz wszystkie dostępności
  getAll: async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/availability`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to fetch availabilities');
    }
  },

  // Pobierz pojedynczą dostępność
  getById: async (id) => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/availability/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to fetch availability');
    }
  },

  // Utwórz nową dostępność
  create: async (availabilityData) => {
    try {
      const response = await axios.post(`${API_BASE_URL}/api/availability`, availabilityData);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to create availability');
    }
  },

  // Zaktualizuj dostępność
  update: async (id, availabilityData) => {
    try {
      const response = await axios.put(`${API_BASE_URL}/api/availability/${id}`, availabilityData);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to update availability');
    }
  },

  // Usuń dostępność
  delete: async (id) => {
    try {
      const response = await axios.delete(`${API_BASE_URL}/api/availability/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to delete availability');
    }
  }
};

export default availabilityService;