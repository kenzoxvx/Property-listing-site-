<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Property Analytics Dashboard</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <style>
    :root {
      --primary: #3b82f6;
      --primary-dark: #2563eb;
      --secondary: #f472b6;
      --accent: #10b981;
      --background: #f9fafb;
      --card-bg: #ffffff;
      --text: #1f2937;
      --text-light: #6b7280;
      --border: #e5e7eb;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
      background-color: var(--background);
      color: var(--text);
      line-height: 1.6;
    }
    
    header {
      background: linear-gradient(to right, var(--primary), var(--primary-dark));
      color: white;
      padding: 2rem;
      position: relative;
      overflow: hidden;
    }
    
    header::after {
      content: '';
      position: absolute;
      bottom: -50px;
      left: 0;
      width: 100%;
      height: 100px;
      background-color: var(--background);
      clip-path: ellipse(50% 60% at 50% 100%);
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }
    
    .property-title {
      font-size: 2rem;
      font-weight: 700;
      margin-top: 0.5rem;
    }
    
    .property-address {
      font-size: 1rem;
      opacity: 0.8;
      margin-bottom: 1rem;
    }
    
    .property-image {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 12px;
      box-shadow: var(--shadow);
      position: absolute;
      top: 2rem;
      right: 2rem;
    }
    
    .dashboard {
      margin-top: -2rem;
      padding-bottom: 2rem;
      position: relative;
      z-index: 10;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .stat-card {
      background-color: var(--card-bg);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: var(--shadow);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .stat-title {
      font-size: 0.875rem;
      color: var(--text-light);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.5rem;
    }
    
    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      display: flex;
      align-items: center;
    }
    
    .stat-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: 8px;
      margin-right: 0.75rem;
    }
    
    .likes .stat-icon {
      background-color: rgba(239, 68, 68, 0.1);
      color: #ef4444;
    }
    
    .comments .stat-icon {
      background-color: rgba(59, 130, 246, 0.1);
      color: #3b82f6;
    }
    
    .views .stat-icon {
      background-color: rgba(16, 185, 129, 0.1);
      color: #10b981;
    }
    
    .leads .stat-icon {
      background-color: rgba(245, 158, 11, 0.1);
      color: #f59e0b;
    }
    
    .chart-container {
      background-color: var(--card-bg);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: var(--shadow);
      margin-bottom: 2rem;
    }
    
    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }
    
    .chart-title {
      font-size: 1.25rem;
      font-weight: 600;
    }
    
    .chart-period {
      display: flex;
      gap: 0.5rem;
    }
    
    .period-btn {
      background-color: transparent;
      border: 1px solid var(--border);
      padding: 0.5rem 0.75rem;
      border-radius: 6px;
      font-size: 0.875rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .period-btn.active {
      background-color: var(--primary);
      color: white;
      border-color: var(--primary);
    }
    
    .chart-canvas {
      height: 300px;
      width: 100%;
    }
    
    .recent-viewers {
      background-color: var(--card-bg);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: var(--shadow);
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }
    
    .section-title {
      font-size: 1.25rem;
      font-weight: 600;
    }
    
    .see-all {
      color: var(--primary);
      font-size: 0.875rem;
      text-decoration: none;
      font-weight: 500;
    }
    
    .see-all:hover {
      text-decoration: underline;
    }
    
    .viewers-list {
      display: grid;
      gap: 1rem;
    }
    
    .viewer-item {
      display: flex;
      align-items: center;
      padding: 0.75rem;
      border-radius: 8px;
      transition: background-color 0.2s ease;
    }
    
    .viewer-item:hover {
      background-color: var(--background);
    }
    
    .viewer-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 1rem;
    }
    
    .viewer-info {
      flex: 1;
    }
    
    .viewer-name {
      font-weight: 500;
    }
    
    .viewer-meta {
      font-size: 0.875rem;
      color: var(--text-light);
    }
    
    .viewer-action {
      background-color: transparent;
      border: 1px solid var(--border);
      color: var(--text);
      padding: 0.5rem 0.75rem;
      border-radius: 6px;
      font-size: 0.875rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .viewer-action:hover {
      background-color: var(--primary);
      color: white;
      border-color: var(--primary);
    }
    
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 500;
      margin-left: 0.5rem;
    }
    
    .badge-lead {
      background-color: rgba(16, 185, 129, 0.1);
      color: #10b981;
    }
    
    .badge-new {
      background-color: rgba(59, 130, 246, 0.1);
      color: #3b82f6;
    }
    
    .tooltip {
      position: absolute;
      padding: 10px;
      background: rgba(0, 0, 0, 0.8);
      color: white;
      border-radius: 4px;
      pointer-events: none;
      transform: translate(-50%, -100%);
      transition: opacity 0.3s;
    }
    
    @media (max-width: 768px) {
      .property-image {
        position: static;
        margin-bottom: 1rem;
      }
      
      header {
        padding: 1.5rem;
      }
      
      .property-title {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="container">
      <img class="property-image" src="/api/placeholder/400/320" alt="Property Image">
      <h6>PROPERTY ANALYTICS</h6>
      <h1 class="property-title">Modern Townhouse</h1>
      <p class="property-address">123 Maple Avenue, Beverly Hills, CA 90210</p>
    </div>
  </header>
  
  <div class="container dashboard">
    <div class="stats-grid">
      <div class="stat-card likes">
        <div class="stat-title">Total Likes</div>
        <div class="stat-value">
          <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8.864.046C7.908-.193 7.02.53 6.956 1.466c-.072 1.051-.23 2.016-.428 2.59-.125.36-.479 1.013-1.04 1.639-.557.623-1.282 1.178-2.131 1.41C2.685 7.288 2 7.87 2 8.72v4.001c0 .845.682 1.464 1.448 1.545 1.07.114 1.564.415 2.068.723l.048.03c.272.165.578.348.97.484.397.136.861.217 1.466.217h3.5c.937 0 1.599-.477 1.934-1.064a1.86 1.86 0 0 0 .254-.912c0-.152-.023-.312-.077-.464.201-.263.38-.578.488-.901.11-.33.172-.762.004-1.149.069-.13.12-.269.159-.403.077-.27.113-.568.113-.857 0-.288-.036-.585-.113-.856a2.144 2.144 0 0 0-.138-.362 1.9 1.9 0 0 0 .234-1.734c-.206-.592-.682-1.1-1.2-1.272-.847-.282-1.803-.276-2.516-.211a9.84 9.84 0 0 0-.443.05 9.365 9.365 0 0 0-.062-4.509A1.38 1.38 0 0 0 9.125.111L8.864.046zM11.5 14.721H8c-.51 0-.863-.069-1.14-.164-.281-.097-.506-.228-.776-.393l-.04-.024c-.555-.339-1.198-.731-2.49-.868-.333-.036-.554-.29-.554-.55V8.72c0-.254.226-.543.62-.65 1.095-.3 1.977-.996 2.614-1.708.635-.71 1.064-1.475 1.238-1.978.243-.7.407-1.768.482-2.85.025-.362.36-.594.667-.518l.262.066c.16.04.258.143.288.255a8.34 8.34 0 0 1-.145 4.725.5.5 0 0 0 .595.644l.003-.001.014-.003.058-.014a8.908 8.908 0 0 1 1.036-.157c.663-.06 1.457-.054 2.11.164.175.058.45.3.57.65.107.308.087.67-.266 1.022l-.353.353.353.354c.043.043.105.141.154.315.048.167.075.37.075.581 0 .212-.027.414-.075.582-.05.174-.111.272-.154.315l-.353.353.353.354c.047.047.109.177.005.488a2.224 2.224 0 0 1-.505.805l-.353.353.353.354c.006.005.041.05.041.17a.866.866 0 0 1-.121.416c-.165.288-.503.56-1.066.56z"/>
            </svg>
          </div>
          <span id="likes-count">254</span>
        </div>
      </div>
      
      <div class="stat-card comments">
        <div class="stat-title">Total Comments</div>
        <div class="stat-value">
          <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
              <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
            </svg>
          </div>
          <span id="comments-count">78</span>
        </div>
      </div>
      
      <div class="stat-card views">
        <div class="stat-title">Total Views</div>
        <div class="stat-value">
          <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
              <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
            </svg>
          </div>
          <span id="views-count">1,653</span>
        </div>
      </div>
      
      <div class="stat-card leads">
        <div class="stat-title">Potential Leads</div>
        <div class="stat-value">
          <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
              <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
              <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
              <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
            </svg>
          </div>
          <span id="leads-count">42</span>
        </div>
      </div>
    </div>
    
    <div class="chart-container">
      <div class="chart-header">
        <h2 class="chart-title">Viewer Analytics</h2>
        <div class="chart-period">
          <button class="period-btn" data-period="week">Week</button>
          <button class="period-btn active" data-period="month">Month</button>
          <button class="period-btn" data-period="year">Year</button>
        </div>
      </div>
      <canvas id="viewership-chart" class="chart-canvas"></canvas>
    </div>
    
    <div class="recent-viewers">
      <div class="section-header">
        <h2 class="section-title">Recent Viewers</h2>
        <a href="#" class="see-all">See all viewers</a>
      </div>
      <div class="viewers-list">
        <div class="viewer-item">
          <img class="viewer-avatar" src="/api/placeholder/100/100" alt="Emma Johnson">
          <div class="viewer-info">
            <div class="viewer-name">Emma Johnson <span class="badge badge-lead">Lead</span></div>
            <div class="viewer-meta">Viewed 5 mins ago • 3rd visit</div>
          </div>
          <button class="viewer-action">Contact</button>
        </div>
        
        <div class="viewer-item">
          <img class="viewer-avatar" src="/api/placeholder/100/100" alt="Marcus Chen">
          <div class="viewer-info">
            <div class="viewer-name">Marcus Chen <span class="badge badge-new">New</span></div>
            <div class="viewer-meta">Viewed 27 mins ago • 1st visit</div>
          </div>
          <button class="viewer-action">Contact</button>
        </div>
        
        <div class="viewer-item">
          <img class="viewer-avatar" src="/api/placeholder/100/100" alt="Sophia Williams">
          <div class="viewer-info">
            <div class="viewer-name">Sophia Williams</div>
            <div class="viewer-meta">Viewed 1 hour ago • 2nd visit</div>
          </div>
          <button class="viewer-action">Contact</button>
        </div>
        
        <div class="viewer-item">
          <img class="viewer-avatar" src="/api/placeholder/100/100" alt="David Miller">
          <div class="viewer-info">
            <div class="viewer-name">David Miller <span class="badge badge-lead">Lead</span></div>
            <div class="viewer-meta">Viewed 3 hours ago • 5th visit</div>
          </div>
          <button class="viewer-action">Contact</button>
        </div>
        
        <div class="viewer-item">
          <img class="viewer-avatar" src="/api/placeholder/100/100" alt="Olivia Garcia">
          <div class="viewer-info">
            <div class="viewer-name">Olivia Garcia</div>
            <div class="viewer-meta">Viewed 5 hours ago • 2nd visit</div>
          </div>
          <button class="viewer-action">Contact</button>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    // Chart data
    const weekData = {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      datasets: [
        {
          label: 'Views',
          data: [42, 47, 52, 58, 89, 112, 95],
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.4,
          fill: true
        },
        {
          label: 'Unique Visitors',
          data: [28, 32, 38, 41, 62, 87, 73],
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          tension: 0.4,
          fill: true
        }
      ]
    };
    
    const monthData = {
      labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      datasets: [
        {
          label: 'Views',
          data: [345, 425, 498, 385],
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.4,
          fill: true
        },
        {
          label: 'Unique Visitors',
          data: [242, 301, 351, 264],
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          tension: 0.4,
          fill: true
        }
      ]
    };
    
    const yearData = {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      datasets: [
        {
          label: 'Views',
          data: [1245, 1342, 1498, 1623, 1542, 1687, 1842, 1756, 1893, 1653, 0, 0],
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.4,
          fill: true
        },
        {
          label: 'Unique Visitors',
          data: [845, 921, 1032, 1145, 1087, 1201, 1312, 1254, 1356, 1174, 0, 0],
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          tension: 0.4,
          fill: true
        }
      ]
    };
    
    // Initialize the chart
    const ctx = document.getElementById('viewership-chart').getContext('2d');
    const viewershipChart = new Chart(ctx, {
      type: 'line',
      data: monthData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: 'rgba(255, 255, 255, 0.2)',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
              title: function(tooltipItems) {
                return tooltipItems[0].label;
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          }
        }
      }
    });
    
    // Handle period button clicks
    document.querySelectorAll('.period-btn').forEach(button => {
      button.addEventListener('click', function() {
        // Remove active class from all buttons
        document.querySelectorAll('.period-btn').forEach(btn => {
          btn.classList.remove('active');
        });
        
        // Add active class to clicked button
        this.classList.add('active');
        
        // Update chart data based on period
        const period = this.getAttribute('data-period');
        let newData;
        
        switch(period) {
          case 'week':
            newData = weekData;
            break;
          case 'month':
            newData = monthData;
            break;
          case 'year':
            newData = yearData;
            break;
          default:
            newData = monthData;
        }
        
        viewershipChart.data = newData;
        viewershipChart.update();
      });
    });
    
    // Animate counting for statistics
    function animateCount(element, target) {
      const obj = { count: 0 };
      const duration = 1500;
      const isThousand = target >= 1000;
      
      gsap.to(obj, {
        count: target,
        duration: duration / 1000,
        onUpdate: function() {
          let count = Math.floor(obj.count);
          if (isThousand) {
            element.textContent = count.toLocaleString();
          } else {
            element.textContent = count;
          }
        }
      });
    }
    
    // Simulate GSAP for animation
    const gsap = {
      to: function(obj, options) {
        const startTime = Date.now();
        const startValue = obj.count;
        const endValue = options.count;
        const duration = options.duration * 1000;
        
        const animate = function() {
          const currentTime = Date.now() - startTime;
          if (currentTime < duration) {
            const progress = currentTime / duration;
            obj.count = startValue + (endValue - startValue) * progress;
            options.onUpdate();
            requestAnimationFrame(animate);
          } else {
            obj.count = endValue;
            options.onUpdate();
          }
        };
        
        animate();
      }
    };
    
    // Start animations when page loads
    document.addEventListener('DOMContentLoaded', function() {
      animateCount(document.getElementById('likes-count'), 254);
      animateCount(document.getElementById('comments-count'), 78);
      animateCount(document.getElementById('views-count'), 1653);
      animateCount(document.getElementById('leads-count'), 42);
    });
    
    // Add hover effect to viewer items
    document.querySelectorAll('.viewer-item').forEach(item => {
      item.addEventListener('mouseenter', function() {
        this.style.backgroundColor = 'var(--background)';
      });
      
      item.addEventListener('mouseleave', function() {
        this.style.backgroundColor = '';
      });
    });
    
    // Add interaction to contact buttons
    document.querySelectorAll('.viewer-action').forEach(button => {
      button.addEventListener('click', function() {
        const viewerName = this.previousElementSibling.querySelector('.viewer-name').textContent.split(' ')[0];
        alert(`Opening chat with ${viewerName}...`);
      });
    });
  </script>
</body>
</html>