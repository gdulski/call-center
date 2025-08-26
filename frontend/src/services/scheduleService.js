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
            throw new Error(errorData.message || errorData.error || `HTTP error! status: ${response.status}`);
        }

        const responseData = await response.json();
        return responseData.data || responseData;
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
            throw new Error(errorData.message || errorData.error || `HTTP error! status: ${response.status}`);
        }

        const responseData = await response.json();
        return responseData.data || responseData;
    }

    async getMetrics(id) {
        const response = await fetch(`${API_BASE_URL}/api/schedules/${id}/metrics`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || `HTTP error! status: ${response.status}`);
        }

        const responseData = await response.json();
        
        // Sprawdź czy odpowiedź ma poprawną strukturę
        if (responseData.success && responseData.data) {
            return responseData.data;
        } else if (responseData.metrics || responseData.validation) {
            // Fallback dla starej struktury
            return responseData;
        } else {
            throw new Error('Nieprawidłowa struktura odpowiedzi z API');
        }
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
            throw new Error(errorData.message || errorData.error || errorData.errors?.join(', ') || `HTTP error! status: ${response.status}`);
        }

        const responseData = await response.json();
        return responseData.data || responseData;
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
            throw new Error(errorData.message || errorData.error || errorData.errors?.join(', ') || `HTTP error! status: ${response.status}`);
        }

        const responseData = await response.json();
        return responseData.data || responseData;
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
            throw new Error(errorData.message || errorData.error || `HTTP error! status: ${response.status}`);
        }

        const responseData = await response.json();
        return responseData.data || responseData;
    }

    async getReassignmentPreview(scheduleId, agentId, newAvailability) {
        const response = await fetch(`${API_BASE_URL}/api/schedules/${scheduleId}/reassignment-preview`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                agentId: agentId,
                newAvailability: newAvailability
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || `HTTP error! status: ${response.status}`);
        }

        const responseData = await response.json();
        return responseData.data || responseData;
    }

    async reassignAgent(scheduleId, agentId, newAvailability) {
        const response = await fetch(`${API_BASE_URL}/api/schedules/${scheduleId}/reassign-agent`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                agentId: agentId,
                newAvailability: newAvailability
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || errorData.error || `HTTP error! status: ${response.status}`);
        }

        const responseData = await response.json();
        return responseData.data || responseData;
    }


}

const scheduleService = new ScheduleService();
export default scheduleService;