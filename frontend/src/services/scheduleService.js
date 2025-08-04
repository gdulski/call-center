const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

class ScheduleService {
    async getAll(filters = {}) {
        const params = new URLSearchParams();
        
        if (filters.queueTypeId) {
            params.append('queueTypeId', filters.queueTypeId);
        }
        
        if (filters.weekStartDate) {
            params.append('weekStartDate', filters.weekStartDate);
        }
        
        const url = `${API_BASE_URL}/api/schedules${params.toString() ? '?' + params.toString() : ''}`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    async getById(id) {
        const response = await fetch(`${API_BASE_URL}/api/schedules/${id}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    async create(scheduleData) {
        const response = await fetch(`${API_BASE_URL}/api/schedules`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(scheduleData),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || errorData.errors?.join(', ') || `HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    async update(id, scheduleData) {
        const response = await fetch(`${API_BASE_URL}/api/schedules/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(scheduleData),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || errorData.errors?.join(', ') || `HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    async delete(id) {
        const response = await fetch(`${API_BASE_URL}/api/schedules/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }


}

const scheduleService = new ScheduleService();
export default scheduleService;