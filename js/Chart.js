// DonutChart
document.addEventListener("DOMContentLoaded", function () {
  const canvas = document.getElementById("donutChart");
  const ctx = canvas.getContext("2d");

  const labels = JSON.parse(canvas.dataset.labels);
  const values = JSON.parse(canvas.dataset.values);

  const data = {
    labels: labels,
    datasets: [
      {
        data: values,
        backgroundColor: [
          "#34D399",
          "#60A5FA",
          "#FBBF24",
          "#F87171",
          "#A78BFA",
          "#F472B6",
          "#818CF8",
          "#2DD4BF",
          "#FB923C",
          "#10B981",
          "#22D3EE",
          "#A3E635",
          "#F43F5E",
          "#E879F9",
          "#8B5CF6",
          "#F59E0B",
          "#38BDF8",
          "#A1A1AA",
          "#737373",
          "#78716C",
        ],
        hoverOffset: 20,
        borderWidth: 0,
      },
    ],
  };

  const options = {
    responsive: true,
    cutout: "70%",
    layout: { padding: 8 },
    plugins: {
      tooltip: {
        enabled: true,
        backgroundColor: "black",
        borderColor: "#ccc",
        borderWidth: 1,
        titleColor: "#fff",
        bodyColor: "#fff",
        callbacks: {
          label: function (context) {
            const label = context.label || "";
            const value = context.raw || 0;
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const percentage = ((value / total) * 100).toFixed(1);
            return `${label}: $${value} (${percentage}%)`;
          },
        },
      },
      legend: { display: false },
    },
  };

  new Chart(ctx, {
    type: "doughnut",
    data: data,
    options: options,
  });
});
