/**
 * Analytics Chart Utilities
 * Contains functions for drawing charts on canvas elements, extracted from scripts.js
 */

function drawPieChart(canvasId, data, labelText) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.error(`Canvas element with ID "${canvasId}" not found`);
        return;
    }
    
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error(`Could not get 2D context for canvas: ${canvasId}`);
        return;
    }
    
    resizeCanvas(canvas);
    
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    const radius = Math.min(centerX, centerY) * 0.6; // Reduced radius slightly
    
    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Draw pie segments
    let startAngle = 0;
    const total = data.datasets[0].data.reduce((sum, value) => sum + value, 0);
    
    if (total === 0) {
        ctx.font = '14px Arial, sans-serif';
        ctx.fillStyle = '#7f8c8d';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', centerX, centerY);
        return;
    }
    
    // Draw each segment
    data.labels.forEach((label, i) => {
        const value = data.datasets[0].data[i];
        if (value === 0) return; // Skip zero-value segments
        
        const sliceAngle = (value / total) * 2 * Math.PI;
        
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
        ctx.closePath();
        
        // Fill segment
        ctx.fillStyle = data.datasets[0].backgroundColor[i] || '#3498db'; // Fallback color
        ctx.fill();
        
        // Calculate label position
        const midAngle = startAngle + sliceAngle / 2;
        const labelRadius = radius * 0.7;
        const labelX = centerX + Math.cos(midAngle) * labelRadius;
        const labelY = centerY + Math.sin(midAngle) * labelRadius;
        
        if (sliceAngle > 0.2) {
            ctx.font = 'bold 12px Arial, sans-serif';
            ctx.fillStyle = '#fff';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            const percent = Math.round((value / total) * 100) + '%';
            ctx.fillText(percent, labelX, labelY);
        }
        
        startAngle += sliceAngle;
    });
    
    // Draw legend
    const legendY = canvas.height - 40;
    const legendX = 10;
    const legendCircleRadius = 5;
    
    ctx.textAlign = 'left';
    ctx.textBaseline = 'middle';
    ctx.font = '10px Arial, sans-serif';
    
    let currentX = legendX;
    let currentY = legendY;
    const lineHeight = 15;
    
    data.labels.forEach((label, i) => {
        const value = data.datasets[0].data[i];
        if (value === 0) return; // Skip zero-value legends
        
        const text = `${label}: ${value}`;
        const textWidth = ctx.measureText(text).width;
        
        if (currentX + textWidth + 30 > canvas.width) {
            currentX = legendX;
            currentY += lineHeight;
        }
        
        // Draw legend color circle
        ctx.beginPath();
        ctx.arc(currentX, currentY, legendCircleRadius, 0, 2 * Math.PI);
        ctx.fillStyle = data.datasets[0].backgroundColor[i] || '#3498db';
        ctx.fill();
        
        // Draw legend text
        ctx.fillStyle = '#2c3e50';
        ctx.fillText(text, currentX + 10, currentY);
        
        currentX += textWidth + 30;
    });
}

function drawBarChart(canvasId, data, yAxisLabel) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.error(`Canvas element with ID "${canvasId}" not found`);
        return;
    }
    
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error(`Could not get 2D context for canvas: ${canvasId}`);
        return;
    }
    
    resizeCanvas(canvas);
    
    // Chart dimensions
    const chartWidth = canvas.width - 60;  // Leave space for y-axis
    const chartHeight = canvas.height - 60; // Leave space for x-axis labels
    const barSpacing = 10;
    const chartX = 40; // X position of chart area
    const chartY = 20; // Y position of chart area
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (!data.labels || !data.labels.length || !data.datasets || !data.datasets[0].data) {
        ctx.font = '14px Arial, sans-serif';
        ctx.fillStyle = '#7f8c8d';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    const maxValue = Math.max(1, ...data.datasets[0].data);
    const barWidth = Math.max(10, (chartWidth - (data.labels.length - 1) * barSpacing) / data.labels.length);
    
    // Draw y-axis
    ctx.beginPath();
    ctx.moveTo(chartX, chartY);
    ctx.lineTo(chartX, chartY + chartHeight);
    ctx.strokeStyle = '#ddd';
    ctx.stroke();
    
    // Draw x-axis
    ctx.beginPath();
    ctx.moveTo(chartX, chartY + chartHeight);
    ctx.lineTo(chartX + chartWidth, chartY + chartHeight);
    ctx.strokeStyle = '#ddd';
    ctx.stroke();
    
    // Draw y-axis grid lines and labels
    const gridLines = 5;
    ctx.textAlign = 'right';
    ctx.fillStyle = '#7f8c8d';
    ctx.font = '10px Arial, sans-serif';
    
    for (let i = 0; i <= gridLines; i++) {
        const y = chartY + chartHeight - (i * chartHeight / gridLines);
        const value = Math.round(maxValue * i / gridLines);
        
        // Grid line
        ctx.beginPath();
        ctx.moveTo(chartX, y);
        ctx.lineTo(chartX + chartWidth, y);
        ctx.strokeStyle = '#eee';
        ctx.stroke();
        
        ctx.fillText(value, chartX - 5, y);
    }
    
    // Y-axis label
    ctx.save();
    ctx.translate(15, chartY + chartHeight / 2);
    ctx.rotate(-Math.PI / 2);
    ctx.textAlign = 'center';
    ctx.fillText(yAxisLabel, 0, 0);
    ctx.restore();
    
    // Draw bars
    data.labels.forEach((label, i) => {
        const value = data.datasets[0].data[i];
        if (value === 0) return; // Skip zero-value bars
        
        const barHeight = (value / maxValue) * chartHeight;
        const x = chartX + i * (barWidth + barSpacing);
        const y = chartY + chartHeight - barHeight;
        
        // Draw bar
        ctx.fillStyle = data.datasets[0].backgroundColor[i] || '#3498db';
        ctx.fillRect(x, y, barWidth, barHeight);
        
        if (barHeight > 15) {
            ctx.fillStyle = '#fff';
            ctx.textAlign = 'center';
            ctx.font = 'bold 10px Arial, sans-serif';
            ctx.fillText(value, x + barWidth / 2, y + 10);
        } else {
            ctx.fillStyle = '#2c3e50';
            ctx.textAlign = 'center';
            ctx.font = 'bold 10px Arial, sans-serif';
            ctx.fillText(value, x + barWidth / 2, y - 5);
        }
        
        ctx.fillStyle = '#2c3e50';
        ctx.textAlign = 'center';
        ctx.font = '10px Arial, sans-serif';
        
        let displayLabel = label;
        if (ctx.measureText(label).width > barWidth * 1.2) {
            displayLabel = label.substring(0, 8) + '...';
        }
        
        ctx.fillText(displayLabel, x + barWidth / 2, chartY + chartHeight + 15);
    });
}

function resizeCanvas(canvas) {
    if (!canvas) return;
    
    const container = canvas.closest('.chart-wrapper');
    if (!container) return;
    
    const rect = container.getBoundingClientRect();
    
    const devicePixelRatio = window.devicePixelRatio || 1;
    
    canvas.style.width = '';
    canvas.style.height = '';
    
    const width = Math.max(300, rect.width);
    const height = Math.max(200, rect.height);
    
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';
    
    canvas.width = width * devicePixelRatio;
    canvas.height = height * devicePixelRatio;
    
    const ctx = canvas.getContext('2d');
    if (ctx) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        ctx.scale(devicePixelRatio, devicePixelRatio);
    }
    
    return { width, height };
}

window.addEventListener('resize', function() {
    clearTimeout(window.resizeTimer);
    window.resizeTimer = setTimeout(function() {
        const chartData = window.globalChartData || {};
        
        if (chartData.pageViews) {
            const canvas = document.getElementById('page-views-chart');
            if (canvas) {
                resizeCanvas(canvas);
                drawBarChart('page-views-chart', chartData.pageViews, 'View Count');
            }
        }
        
        if (chartData.apiUsage) {
            const canvas = document.getElementById('api-usage-chart');
            if (canvas) {
                resizeCanvas(canvas);
                drawPieChart('api-usage-chart', chartData.apiUsage, 'API Calls');
            }
        }
        
        if (chartData.bookStatus) {
            const canvas = document.getElementById('book-status-chart');
            if (canvas) {
                resizeCanvas(canvas);
                drawPieChart('book-status-chart', chartData.bookStatus, 'Book Status');
            }
        }
        
        if (chartData.userActivity) {
            const canvas = document.getElementById('user-activity-chart');
            if (canvas && window.updateUserActivityChart) {
                resizeCanvas(canvas);
                window.updateUserActivityChart(chartData.userActivity.originalData || []);
            }
        }
    }, 250);
});

/**
 * Updates the API usage chart with data from the API
 * @param {Array} apiData - The API usage data
 */
function updateApiUsageChart(apiData) {
    if (!apiData || !apiData.length) {
        return;
    }
    
    const labels = [];
    const callCounts = [];
    const responseTimesMs = [];
    const backgroundColors = [];
    
    // Generate colors
    const baseColors = [
        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', 
        '#1abc9c', '#d35400', '#34495e', '#2980b9', '#27ae60'
    ];
    
    // Process data
    apiData.forEach((item, index) => {
        const label = `${item.endpoint} (${item.method})`;
        labels.push(label);
        callCounts.push(item.call_count);
        responseTimesMs.push(Math.round(item.avg_response_time));
        backgroundColors.push(baseColors[index % baseColors.length]);
    });
    
    // Create chart data
    const chartData = {
        labels: labels,
        datasets: [{
            data: callCounts,
            backgroundColor: backgroundColors
        }]
    };
    
    // Create response time data
    const responseTimeData = {
        labels: labels,
        datasets: [{
            data: responseTimesMs,
            backgroundColor: backgroundColors
        }]
    };
    
    // Draw charts
    drawBarChart('api-usage-chart', chartData, 'Call Count');
    
    const tableBody = document.querySelector('#api-usage-table tbody');
    if (tableBody) {
        let tableHTML = '';
        
        apiData.forEach(item => {
            tableHTML += `<tr>
                <td>${item.endpoint}</td>
                <td>${item.method}</td>
                <td>${item.call_count}</td>
                <td>${Math.round(item.avg_response_time)} ms</td>
                <td>${item.unique_users}</td>
            </tr>`;
        });
        
        tableBody.innerHTML = tableHTML;
    }
} 