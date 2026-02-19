import { createContext, useContext, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { authService } from '../services/authService';
import type { LoginRequest, RegisterRequest, User } from '../types/auth';

interface AuthContextValue {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (credentials: LoginRequest) => Promise<void>;
  register: (data: RegisterRequest) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const hydrate = async () => {
      if (authService.isAuthenticated()) {
        try {
          const me = await authService.me();
          setUser(me);
        } catch {
          authService.removeToken();
        }
      }
      setIsLoading(false);
    };

    hydrate();
  }, []);

  const login = async (credentials: LoginRequest) => {
    const response = await authService.login(credentials);
    authService.setToken(response.data.token);
    setUser(response.data.user);
  };

  const register = async (data: RegisterRequest) => {
    const response = await authService.register(data);
    authService.setToken(response.data.token);
    setUser(response.data.user);
  };

  const logout = async () => {
    try {
      await authService.logout();
    } catch {
      authService.removeToken();
    }
    setUser(null);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        isAuthenticated: !!user,
        isLoading,
        login,
        register,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
