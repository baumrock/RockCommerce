<script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>

<div class="alert alert-info" style="margin-bottom: 20px; padding: 15px; border-left: 5px solid #17a2b8; background-color: #f8f9fa;">
  <strong>📊 Dashboard Preview</strong><br>
  This is a demo chart. A comprehensive dashboard with real-time data and basic analytics is currently under development and will be available in a future update.
</div>

<div id="chart" style="width: 100%; height: 400px;"></div>
<script>
  // Initialize ECharts instance
  var chartDom = document.getElementById('chart');
  var myChart = echarts.init(chartDom);

  // Chart configuration
  var option = {
    xAxis: {
      data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
    },
    yAxis: {},
    series: [{
      type: 'bar',
      data: [23, 24, 18, 25, 27, 28, 25]
    }]
  };

  // Set the configuration and render the chart
  myChart.setOption(option);
</script>