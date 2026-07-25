/**
 * Dashboard charts, rendered with amCharts 5.
 *
 * This module is dynamically imported by app.js only when a [data-chart]
 * element is present, so the (large) amCharts bundle never loads on other
 * pages. Each chart div carries its dataset in a `data-series` JSON attribute.
 */
import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import * as am5percent from '@amcharts/amcharts5/percent';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

const EMERALD = '#059669';
const RED = '#ef4444';

/** Read and parse the JSON dataset from a chart element. */
function readSeries(el) {
    try {
        return JSON.parse(el.dataset.series || '[]');
    } catch {
        return [];
    }
}

/** Income vs Expense grouped column chart. */
function renderIncomeExpense(root, data) {
    root.setThemes([am5themes_Animated.new(root)]);

    const chart = root.container.children.push(
        am5xy.XYChart.new(root, { panX: false, panY: false, layout: root.verticalLayout })
    );

    const xAxis = chart.xAxes.push(
        am5xy.CategoryAxis.new(root, {
            categoryField: 'label',
            renderer: am5xy.AxisRendererX.new(root, { cellStartLocation: 0.15, cellEndLocation: 0.85 }),
        })
    );
    xAxis.data.setAll(data);

    const yAxis = chart.yAxes.push(
        am5xy.ValueAxis.new(root, { min: 0, renderer: am5xy.AxisRendererY.new(root, {}) })
    );

    const makeSeries = (name, field, color) => {
        const series = chart.series.push(
            am5xy.ColumnSeries.new(root, {
                name,
                xAxis,
                yAxis,
                valueYField: field,
                categoryXField: 'label',
                fill: am5.color(color),
                stroke: am5.color(color),
            })
        );
        series.columns.template.setAll({ cornerRadiusTL: 4, cornerRadiusTR: 4, width: am5.percent(90) });
        series.data.setAll(data);
        return series;
    };

    makeSeries('Income', 'income', EMERALD);
    makeSeries('Expense', 'expense', RED);

    chart.set('cursor', am5xy.XYCursor.new(root, {}));
    chart.children.unshift(am5.Legend.new(root, { centerX: am5.percent(50), x: am5.percent(50) }));
}

/** Monthly expense trend line chart. */
function renderTrend(root, data) {
    root.setThemes([am5themes_Animated.new(root)]);

    const chart = root.container.children.push(
        am5xy.XYChart.new(root, { panX: false, panY: false })
    );

    const xAxis = chart.xAxes.push(
        am5xy.CategoryAxis.new(root, {
            categoryField: 'label',
            renderer: am5xy.AxisRendererX.new(root, {}),
        })
    );
    xAxis.data.setAll(data);

    const yAxis = chart.yAxes.push(
        am5xy.ValueAxis.new(root, { min: 0, renderer: am5xy.AxisRendererY.new(root, {}) })
    );

    const series = chart.series.push(
        am5xy.LineSeries.new(root, {
            name: 'Expense',
            xAxis,
            yAxis,
            valueYField: 'expense',
            categoryXField: 'label',
            stroke: am5.color(RED),
            tooltip: am5.Tooltip.new(root, { labelText: '{categoryX}: ₹{valueY}' }),
        })
    );
    series.strokes.template.setAll({ strokeWidth: 3 });
    series.bullets.push(() =>
        am5.Bullet.new(root, {
            sprite: am5.Circle.new(root, { radius: 4, fill: am5.color(RED) }),
        })
    );
    series.data.setAll(data);

    chart.set('cursor', am5xy.XYCursor.new(root, {}));
}

/** Category-wise spending donut chart. */
function renderCategory(root, data) {
    root.setThemes([am5themes_Animated.new(root)]);

    const chart = root.container.children.push(
        am5percent.PieChart.new(root, { layout: root.verticalLayout, innerRadius: am5.percent(45) })
    );

    const series = chart.series.push(
        am5percent.PieSeries.new(root, {
            valueField: 'total',
            categoryField: 'name',
        })
    );
    series.labels.template.setAll({ text: '{category}', fontSize: 12 });
    series.data.setAll(data);

    chart.children.unshift(am5.Legend.new(root, { centerX: am5.percent(50), x: am5.percent(50), marginTop: 12 }));
}

const RENDERERS = {
    'income-expense': renderIncomeExpense,
    trend: renderTrend,
    category: renderCategory,
};

document.querySelectorAll('[data-chart]').forEach((el) => {
    const renderer = RENDERERS[el.dataset.chart];
    if (!renderer) return;

    const root = am5.Root.new(el);
    // amCharts adds its own attribution logo element; keep the layout tidy.
    root._logo?.dispose();
    renderer(root, readSeries(el));
});
