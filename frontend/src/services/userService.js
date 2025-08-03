import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || '';

const userService = {
  // Pobierz wszystkich użytkowników
  getAll: async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/users`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to fetch users');
    }
  },

  // Pobierz pojedynczego użytkownika
  getById: async (id) => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/users/${id}`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to fetch user');
    }
  },

  // Utwórz nowego użytkownika
  create: async (userData) => {
    try {
      const response = await axios.post(`${API_BASE_URL}/api/users`, userData);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to create user');
    }
  },

  // Zaktualizuj użytkownika
  update: async (id, userData) => {
    try {
      const response = await axios.put(`${API_BASE_URL}/api/users/${id}`, userData);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to update user');
    }
  },

  // Pobierz dostępne role
  getRoles: async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/users/roles`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to fetch roles');
    }
  },

  // Pobierz dostępne typy kolejek
  getQueueTypes: async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/api/users/queue-types`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.error || 'Failed to fetch queue types');
    }
  }
};

export default userService;