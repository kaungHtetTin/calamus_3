import React from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Box,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  IconButton,
  ListItemIcon,
  ListItemText,
  Menu,
  MenuItem,
  Paper,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';
import { Add as AddIcon, Edit as EditIcon, Delete as DeleteIcon, MoreVert as MoreVertIcon } from '@mui/icons-material';

export default function SaveReplies({ saveReplies }) {
  const { admin_app_url } = usePage().props;
  const rows = Array.isArray(saveReplies) ? saveReplies : [];

  const [openDialog, setOpenDialog] = React.useState(false);
  const [editing, setEditing] = React.useState(null);
  const [rowMenu, setRowMenu] = React.useState({ anchorEl: null, row: null });
  const rowMenuOpen = Boolean(rowMenu.anchorEl);

  const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm({
    title: '',
    message: '',
  });

  const handleOpenAdd = () => {
    setEditing(null);
    reset();
    clearErrors();
    setData({
      title: '',
      message: '',
    });
    setOpenDialog(true);
  };

  const handleOpenEdit = (row) => {
    setEditing(row);
    clearErrors();
    setData({
      title: row.title || '',
      message: row.message || '',
    });
    setOpenDialog(true);
  };

  const handleClose = () => {
    setOpenDialog(false);
    setEditing(null);
    reset();
    clearErrors();
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editing) {
      patch(`${admin_app_url}/save-replies/${editing.id}`, { preserveScroll: true, onSuccess: handleClose });
      return;
    }
    post(`${admin_app_url}/save-replies`, { preserveScroll: true, onSuccess: handleClose });
  };

  const handleDelete = (row) => {
    if (!confirm(`Delete saved reply "${row.title}"?`)) return;
    router.delete(`${admin_app_url}/save-replies/${row.id}`, { preserveScroll: true });
  };

  const openRowMenu = (event, row) => {
    setRowMenu({ anchorEl: event.currentTarget, row });
  };

  const closeRowMenu = () => {
    setRowMenu({ anchorEl: null, row: null });
  };

  return (
    <Box>
      <Head title="Save Replies" />

      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Save Replies
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage pre-written replies for admins.
            </Typography>
          </Box>
          <Button variant="contained" size="small" startIcon={<AddIcon />} onClick={handleOpenAdd}>
            Add Reply
          </Button>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
            <Table size="small" sx={{ tableLayout: 'auto', '& .MuiTableCell-root': { px: 1, py: 0.75, verticalAlign: 'top' } }}>
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700, minWidth: 220 }}>Title</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 360 }}>Message</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 110 }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {rows.map((row) => (
                  <TableRow key={`save-reply-${row.id}`} hover>
                    <TableCell>
                      <Typography variant="body2" sx={{ fontWeight: 700 }}>
                        {row.title}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap' }}>
                        {row.message}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <IconButton size="small" aria-label="actions" onClick={(e) => openRowMenu(e, row)}>
                        <MoreVertIcon fontSize="small" />
                      </IconButton>
                    </TableCell>
                  </TableRow>
                ))}
                {rows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={3}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No saved replies found.
                      </Typography>
                    </TableCell>
                  </TableRow>
                ) : null}
              </TableBody>
            </Table>
          </TableContainer>
        </Paper>
      </Stack>

      <Dialog open={openDialog} onClose={handleClose} maxWidth="md" fullWidth>
        <DialogTitle sx={{ fontWeight: 700 }}>{editing ? 'Edit Reply' : 'Add Reply'}</DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          <Stack spacing={1.25}>
            <TextField
              label="Title"
              value={data.title}
              onChange={(e) => setData('title', e.target.value)}
              error={Boolean(errors.title)}
              helperText={errors.title}
              fullWidth
              size="small"
            />

            <TextField
              label="Message"
              value={data.message}
              onChange={(e) => setData('message', e.target.value)}
              error={Boolean(errors.message)}
              helperText={errors.message}
              fullWidth
              size="small"
              minRows={6}
              multiline
            />
          </Stack>
        </DialogContent>
        <Divider />
        <DialogActions sx={{ p: 2 }}>
          <Button onClick={handleClose} disabled={processing}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} variant="contained" disabled={processing}>
            {editing ? 'Save Changes' : 'Create'}
          </Button>
        </DialogActions>
      </Dialog>

      <Menu
        anchorEl={rowMenu.anchorEl}
        open={rowMenuOpen}
        onClose={closeRowMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = rowMenu.row;
            closeRowMenu();
            if (row) handleOpenEdit(row);
          }}
        >
          <ListItemIcon>
            <EditIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText>Edit</ListItemText>
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = rowMenu.row;
            closeRowMenu();
            if (row) handleDelete(row);
          }}
        >
          <ListItemIcon>
            <DeleteIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText>Delete</ListItemText>
        </MenuItem>
      </Menu>
    </Box>
  );
}

SaveReplies.layout = (page) => <AdminLayout children={page} />;
