import React from 'react';
import {
  Box,
  FormControl,
  MenuItem,
  Pagination,
  Select,
  Stack,
  Typography,
} from '@mui/material';

export default function AdminPagination({
  total = 0,
  page = 1,
  perPage = 15,
  lastPage,
  from,
  to,
  itemLabel = 'items',
  onPageChange,
  rowsPerPageOptions = [],
  onRowsPerPageChange,
  sx,
}) {
  const safeTotal = Math.max(0, Number(total) || 0);
  const safePerPage = Math.max(1, Number(perPage) || 1);
  const calculatedLastPage = Math.max(1, Math.ceil(safeTotal / safePerPage));
  const safeLastPage = Math.max(1, Number(lastPage) || calculatedLastPage);
  const safePage = Math.min(safeLastPage, Math.max(1, Number(page) || 1));
  const safeFrom = safeTotal === 0
    ? 0
    : Math.max(1, Number(from) || ((safePage - 1) * safePerPage) + 1);
  const safeTo = safeTotal === 0
    ? 0
    : Math.min(safeTotal, Math.max(safeFrom, Number(to) || safePage * safePerPage));
  const pageSizeOptions = Array.from(new Set([
    ...rowsPerPageOptions.map(Number).filter((value) => value > 0),
    safePerPage,
  ])).sort((a, b) => a - b);
  const canChangePageSize = typeof onRowsPerPageChange === 'function' && pageSizeOptions.length > 1;

  return (
    <Box
      sx={{
        display: 'flex',
        flexDirection: { xs: 'column', sm: 'row' },
        justifyContent: 'space-between',
        alignItems: { xs: 'flex-start', sm: 'center' },
        gap: 1.25,
        px: 1,
        py: 1.5,
        ...sx,
      }}
    >
      <Typography variant="caption" color="text.secondary">
        Showing {safeFrom} to {safeTo} of {safeTotal} {itemLabel}
      </Typography>

      <Stack direction="row" spacing={1.5} alignItems="center" sx={{ alignSelf: { xs: 'stretch', sm: 'auto' } }}>
        {canChangePageSize ? (
          <Stack direction="row" spacing={0.75} alignItems="center">
            <Typography variant="caption" color="text.secondary" sx={{ whiteSpace: 'nowrap' }}>
              Rows per page
            </Typography>
            <FormControl size="small" variant="outlined">
              <Select
                value={safePerPage}
                onChange={(event) => onRowsPerPageChange(Number(event.target.value))}
                inputProps={{ 'aria-label': 'Rows per page' }}
                sx={{
                  height: 30,
                  minWidth: 68,
                  borderRadius: 1.5,
                  fontSize: '0.75rem',
                }}
              >
                {pageSizeOptions.map((option) => (
                  <MenuItem key={option} value={option}>{option}</MenuItem>
                ))}
              </Select>
            </FormControl>
          </Stack>
        ) : null}

        <Pagination
          count={safeLastPage}
          page={safePage}
          onChange={onPageChange}
          color="primary"
          size="small"
          sx={{
            ml: 'auto',
            '& .MuiPaginationItem-root': { borderRadius: 1.5 },
          }}
        />
      </Stack>
    </Box>
  );
}
