import { Fragment } from 'react';

function getCellValue(column, row) {
    if (typeof column.render === 'function') {
        return column.render(row);
    }

    const value = row[column.key];

    return value === null || value === undefined || value === '' ? '—' : value;
}

function getRowKey(row, index, rowKey) {
    if (typeof rowKey === 'function') {
        return rowKey(row, index);
    }

    return row[rowKey] ?? index;
}

/**
 * Reusable data table contract.
 *
 * Columns can opt into a mobile role: title, meta, detail, or hidden.
 * The same data contract can feed a native list/card adapter later.
 */
export default function DataTable({
    caption = 'Data table',
    columns = [],
    rows = [],
    rowKey = 'id',
    actions,
    emptyState,
    className = '',
}) {
    const mobileTitle = columns.find((column) => column.mobile === 'title')
        || columns.find((column) => column.mobile !== 'hidden')
        || columns[0];
    const mobileMeta = columns.filter((column) => column.mobile === 'meta');
    const mobileDetails = columns.filter((column) => column.mobile !== 'hidden' && column !== mobileTitle && column.mobile !== 'meta');

    if (rows.length === 0 && emptyState) {
        return (
            <div className={`ds-table-empty ${className}`.trim()} role="status">
                <div className="ds-empty-icon"><i className={`bi ${emptyState.icon || 'bi-inbox'}`} aria-hidden="true" /></div>
                <strong>{emptyState.title}</strong>
                {emptyState.description && <p>{emptyState.description}</p>}
            </div>
        );
    }

    return (
        <div className={`ds-data-table ${className}`.trim()}>
            <div className="ds-table-wrap ds-table-desktop">
                <table className="ds-table">
                    <caption className="sr-only">{caption}</caption>
                    <thead>
                        <tr>
                            {columns.map((column) => (
                                <th key={column.key} scope="col" className={column.align ? `is-${column.align}` : ''}>
                                    {column.label}
                                </th>
                            ))}
                            {actions && <th scope="col" className="is-actions"><span className="sr-only">Actions</span></th>}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr key={getRowKey(row, index, rowKey)}>
                                {columns.map((column) => (
                                    <td key={column.key} className={column.align ? `is-${column.align}` : ''}>
                                        {getCellValue(column, row)}
                                    </td>
                                ))}
                                {actions && <td className="is-actions">{actions(row)}</td>}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="ds-table-cards" role="list" aria-label={`${caption} mobile view`}>
                {rows.map((row, index) => (
                    <article className="ds-table-card" role="listitem" key={getRowKey(row, index, rowKey)}>
                        <div className="ds-table-card-head">
                            <div className="ds-table-card-title">
                                <strong>{getCellValue(mobileTitle, row)}</strong>
                                {mobileMeta.length > 0 && (
                                    <span>{mobileMeta.map((column, metaIndex) => (
                                        <Fragment key={column.key}>
                                            {metaIndex > 0 && <span aria-hidden="true"> · </span>}
                                            {getCellValue(column, row)}
                                        </Fragment>
                                    ))}</span>
                                )}
                            </div>
                            {actions && <div className="ds-table-card-action">{actions(row)}</div>}
                        </div>
                        {mobileDetails.length > 0 && (
                            <dl className="ds-table-card-details">
                                {mobileDetails.map((column) => (
                                    <div key={column.key}>
                                        <dt>{column.label}</dt>
                                        <dd>{getCellValue(column, row)}</dd>
                                    </div>
                                ))}
                            </dl>
                        )}
                    </article>
                ))}
            </div>
        </div>
    );
}
