/**
 * ImpersonationBanner — Sticky banner shown when an admin is impersonating a student.
 * Displays the impersonated user's name and a button to stop impersonating.
 */
import { useAuth } from '@/contexts/AuthContext';
import { Button } from '@/components/ui/button';
import { Eye, X, Shield } from 'lucide-react';

export function ImpersonationBanner() {
  const { isImpersonating, impersonatedUser, stopImpersonating, realUser } = useAuth();

  if (!isImpersonating || !impersonatedUser) return null;

  return (
    <div className="fixed top-0 left-0 right-0 z-[100] bg-gradient-to-r from-amber-500 to-amber-600 text-white px-4 py-2.5 shadow-lg border-b border-amber-600">
      <div className="max-w-7xl mx-auto flex items-center justify-between gap-3">
        <div className="flex items-center gap-2.5 text-sm font-medium">
          <div className="p-1 bg-white/20 rounded">
            <Eye className="w-3.5 h-3.5" />
          </div>
          <span>
            Viewing as <strong>{impersonatedUser.name}</strong>
            <span className="hidden sm:inline text-amber-100 ml-1">({impersonatedUser.email})</span>
          </span>
          {realUser && (
            <span className="hidden md:flex items-center gap-1 text-amber-100 ml-1 text-xs">
              <Shield className="w-3 h-3" />
              Logged in as {realUser.name}
            </span>
          )}
        </div>
        <Button
          variant="ghost"
          size="sm"
          className="h-7 px-3 text-xs font-semibold text-white bg-white/15 hover:bg-white/25 hover:text-white border border-white/20 rounded-md"
          onClick={stopImpersonating}
        >
          <X className="w-3 h-3 mr-1" />
          Stop Impersonating
        </Button>
      </div>
    </div>
  );
}
