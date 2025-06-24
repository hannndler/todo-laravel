import { useEffect, useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { AuthenticatedLayout } from '@/layouts/authenticated-layout';
import { useAuthStore } from '@/stores/auth-store';
import { Edit, Trash2, Plus } from 'lucide-react';

interface Team {
  id: number;
  name: string;
  description?: string;
  created_at: string;
  updated_at: string;
}

export default function TeamsPage() {
  const [teams, setTeams] = useState<Team[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [showEdit, setShowEdit] = useState<Team | null>(null);
  const [showDelete, setShowDelete] = useState<Team | null>(null);
  const [form, setForm] = useState({ name: '', description: '' });
  const [formError, setFormError] = useState('');
  const token = useAuthStore((state) => state.token);

  const loadTeams = async () => {
    try {
      setLoading(true);
      const response = await fetch('/api/teams', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });
      if (response.ok) {
        const data = await response.json();
        setTeams(data.data || []);
      }
    } catch {
      console.error('Error loading teams:');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadTeams();
  }, [token]);

  // Crear equipo
  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError('');
    try {
      const response = await fetch('/api/teams', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(form),
      });
      if (response.ok) {
        setShowCreate(false);
        setForm({ name: '', description: '' });
        loadTeams();
      } else {
        const data = await response.json();
        setFormError(data.message || 'Error al crear el equipo');
      }
    } catch {
      setFormError('Error al crear el equipo');
    }
  };

  // Editar equipo
  const handleEdit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!showEdit) return;
    setFormError('');
    try {
      const response = await fetch(`/api/teams/${showEdit.id}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(form),
      });
      if (response.ok) {
        setShowEdit(null);
        setForm({ name: '', description: '' });
        loadTeams();
      } else {
        const data = await response.json();
        setFormError(data.message || 'Error al editar el equipo');
      }
    } catch {
      setFormError('Error al editar el equipo');
    }
  };

  // Eliminar equipo
  const handleDelete = async () => {
    if (!showDelete) return;
    try {
      const response = await fetch(`/api/teams/${showDelete.id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        },
      });
      if (response.ok) {
        setShowDelete(null);
        loadTeams();
      }
    } catch {
      // Manejar error si es necesario
    }
  };

  return (
    <AuthenticatedLayout>
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Equipos</h1>
            <p className="text-gray-600 dark:text-gray-400">Gestiona y organiza tus equipos</p>
          </div>
          <Button onClick={() => { setShowCreate(true); setForm({ name: '', description: '' }); }}>
            <Plus className="mr-2 h-4 w-4" /> Nuevo equipo
          </Button>
        </div>

        <div className="space-y-4">
          {loading ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
              <p className="text-gray-600">Cargando equipos...</p>
            </div>
          ) : teams.length === 0 ? (
            <Card>
              <CardContent className="text-center py-8">
                <p className="text-gray-600">No se encontraron equipos</p>
              </CardContent>
            </Card>
          ) : (
            teams.map((team) => (
              <Card key={team.id} className="hover:shadow-md transition-shadow">
                <CardContent className="p-6">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <h3 className="font-medium text-lg text-gray-900 dark:text-white">{team.name}</h3>
                      {team.description && (
                        <p className="text-gray-600 dark:text-gray-400 mt-1">{team.description}</p>
                      )}
                    </div>
                    <div className="flex gap-2">
                      <Button size="icon" variant="ghost" onClick={() => { setShowEdit(team); setForm({ name: team.name, description: team.description || '' }); }} title="Editar equipo">
                        <Edit className="h-5 w-5" />
                      </Button>
                      <Button size="icon" variant="destructive" onClick={() => setShowDelete(team)} title="Eliminar equipo">
                        <Trash2 className="h-5 w-5" />
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))
          )}
        </div>

        {/* Modal Crear equipo */}
        <Dialog open={showCreate} onOpenChange={setShowCreate}>
          <DialogTrigger asChild></DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Nuevo equipo</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleCreate} className="space-y-4">
              <Input
                placeholder="Nombre del equipo"
                value={form.name}
                onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
                required
              />
              <Input
                placeholder="Descripción (opcional)"
                value={form.description}
                onChange={e => setForm(f => ({ ...f, description: e.target.value }))}
              />
              {formError && <p className="text-red-600 text-sm">{formError}</p>}
              <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={() => setShowCreate(false)}>Cancelar</Button>
                <Button type="submit">Crear</Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>

        {/* Modal Editar equipo */}
        <Dialog open={!!showEdit} onOpenChange={v => { if (!v) setShowEdit(null); }}>
          <DialogTrigger asChild></DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Editar equipo</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleEdit} className="space-y-4">
              <Input
                placeholder="Nombre del equipo"
                value={form.name}
                onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
                required
              />
              <Input
                placeholder="Descripción (opcional)"
                value={form.description}
                onChange={e => setForm(f => ({ ...f, description: e.target.value }))}
              />
              {formError && <p className="text-red-600 text-sm">{formError}</p>}
              <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={() => setShowEdit(null)}>Cancelar</Button>
                <Button type="submit">Guardar</Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>

        {/* Modal Eliminar equipo */}
        <Dialog open={!!showDelete} onOpenChange={v => { if (!v) setShowDelete(null); }}>
          <DialogTrigger asChild></DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Eliminar equipo</DialogTitle>
            </DialogHeader>
            <div className="py-4">
              <p>¿Estás seguro de que deseas eliminar el equipo <b>{showDelete?.name}</b>? Esta acción no se puede deshacer.</p>
            </div>
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => setShowDelete(null)}>Cancelar</Button>
              <Button type="button" variant="destructive" onClick={handleDelete}>Eliminar</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>
    </AuthenticatedLayout>
  );
}