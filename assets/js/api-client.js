/**
 * EduAttend — Attendance API client
 */
class EduAttendAPI {
    constructor(baseURL) {
        this.baseURL = baseURL || '/pages/api/attendance.php';
    }

    async request(action, payload) {
        const url = this.baseURL + (this.baseURL.includes('?') ? '&' : '?') + 'action=' + encodeURIComponent(action);
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload || {})
        });
        if (!res.ok) {
            throw new Error('Request failed: ' + res.status);
        }
        return res.json();
    }

    async getAttendanceStats() {
        return this.request('stats', {});
    }

    async markAttendance(studentId, status) {
        return this.request('mark', { student_id: studentId, status: status });
    }
}

window.EduAttendAPI = EduAttendAPI;
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EduAttendAPI;
}
