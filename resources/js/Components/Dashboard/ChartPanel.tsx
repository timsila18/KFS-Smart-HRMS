import { Chart, BarController, BarElement, CategoryScale, DoughnutController, ArcElement, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip } from 'chart.js';
import { useEffect, useRef } from 'react';

Chart.register(BarController, BarElement, CategoryScale, DoughnutController, ArcElement, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);

type ChartPanelProps = {
    title: string;
    description: string;
    type: 'line' | 'bar' | 'doughnut';
    labels: string[];
    values: number[];
    currency?: boolean;
};

export function ChartPanel({ title, description, type, labels, values, currency = false }: ChartPanelProps) {
    const canvasRef = useRef<HTMLCanvasElement | null>(null);

    useEffect(() => {
        if (!canvasRef.current) {
            return;
        }

        const chart = new Chart(canvasRef.current, {
            type,
            data: {
                labels,
                datasets: [
                    {
                        label: title,
                        data: values,
                        borderColor: '#26734d',
                        backgroundColor: type === 'doughnut'
                            ? ['#26734d', '#d9a51c', '#4f8a6b', '#7aa95c', '#b56b45', '#2f6f7e', '#98b36d', '#7f9f88']
                            : 'rgba(38, 115, 77, 0.18)',
                        pointBackgroundColor: '#26734d',
                        tension: 0.35,
                        borderWidth: 2,
                        borderRadius: type === 'bar' ? 6 : 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: type === 'doughnut', position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const value = Number(context.raw ?? 0);
                                return currency ? `KES ${value.toLocaleString()}` : value.toLocaleString();
                            },
                        },
                    },
                },
                scales: type === 'doughnut' ? undefined : {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => currency ? `KES ${Number(value).toLocaleString()}` : Number(value).toLocaleString(),
                        },
                    },
                },
            },
        });

        return () => chart.destroy();
    }, [currency, description, labels, title, type, values]);

    return (
        <section className="rounded-lg border bg-card p-5 text-card-foreground shadow-sm">
            <div className="mb-5">
                <h2 className="text-lg font-semibold">{title}</h2>
                <p className="mt-1 text-sm text-muted-foreground">{description}</p>
            </div>
            <div className="h-72">
                {labels.length > 0 ? (
                    <canvas ref={canvasRef} aria-label={title} />
                ) : (
                    <div className="flex h-full items-center justify-center rounded-md border border-dashed text-sm text-muted-foreground">
                        No data available yet.
                    </div>
                )}
            </div>
        </section>
    );
}
