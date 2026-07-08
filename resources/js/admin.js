document.addEventListener('DOMContentLoaded', function () {
    fetch('/api/risk')
        .then(r => r.json())
        .then(data => {
            const risks = Array.isArray(data) ? data : [];
            const ctx = document.getElementById('adminRiskChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: risks.map(r => r.country?.name ?? 'N/A'),
                    datasets: [{
                        label: 'Risk Score',
                        data: risks.map(r => r.total_score || 0),
                        backgroundColor: risks.map(r =>
                            r.total_score <= 30 ? '#198754' : r.total_score <= 60 ? '#ffc107' : '#dc3545'
                        )
                    }]
                },
                options: {
                    scales: { y: { beginAtZero: true, max: 100 } }
                }
            });
        });
});
