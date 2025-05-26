<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Usuários';
    
    protected static ?string $modelLabel = 'Usuário';
    
    protected static ?string $pluralModelLabel = 'Usuários';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Pessoais')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('João Silva'),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('joao.silva@empresa.com'),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('(11) 99999-9999'),
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Foto do Perfil')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Informações Profissionais')
                    ->schema([
                        Forms\Components\TextInput::make('department')
                            ->label('Departamento')
                            ->maxLength(255)
                            ->placeholder('Recursos Humanos'),
                        Forms\Components\TextInput::make('position')
                            ->label('Cargo')
                            ->maxLength(255)
                            ->placeholder('Analista de RH'),
                        Forms\Components\Toggle::make('is_supervisor')
                            ->label('É Supervisor?')
                            ->helperText('Supervisores podem acessar relatórios avançados')
                            ->disabled(fn () => !auth()->user()->is_supervisor)
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Usuário Ativo')
                            ->helperText('Usuários inativos não podem acessar o sistema')
                            ->disabled(fn () => !auth()->user()->is_supervisor)
                            ->default(true),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Segurança')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText('Mínimo 8 caracteres'),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Senha')
                            ->password()
                            ->dehydrated(false)
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->same('password'),
                    ])->columns(2)
                    ->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        return 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF';
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('E-mail copiado!')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable()
                    ->placeholder('Não informado')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('department')
                    ->label('Departamento')
                    ->searchable()
                    ->placeholder('Não informado')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Cargo')
                    ->searchable()
                    ->placeholder('Não informado')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_supervisor')
                    ->label('Supervisor')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('vehicleUsages_count')
                    ->label('Usos de Veículos')
                    ->counts('vehicleUsages')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('E-mail Verificado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Não verificado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->label('Departamento')
                    ->options(function () {
                        return User::whereNotNull('department')
                            ->distinct()
                            ->pluck('department', 'department')
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('position')
                    ->label('Cargo')
                    ->options(function () {
                        return User::whereNotNull('position')
                            ->distinct()
                            ->pluck('position', 'position')
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),
                    
                Tables\Filters\TernaryFilter::make('is_supervisor')
                    ->label('É Supervisor')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas Supervisores')
                    ->falseLabel('Apenas Usuários Comuns'),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas Ativos')
                    ->falseLabel('Apenas Inativos'),
                    
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('E-mail Verificado')
                    ->placeholder('Todos')
                    ->trueLabel('Verificados')
                    ->falseLabel('Não Verificados')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                    ),
                    
                Tables\Filters\Filter::make('created_at')
                    ->label('Data de Criação')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('De')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Até')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Criado de: ' . \Carbon\Carbon::parse($data['created_from'])->format('d/m/Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Criado até: ' . \Carbon\Carbon::parse($data['created_until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
                    
                Tables\Filters\Filter::make('no_usage')
                    ->label('Sem Uso de Veículos')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDoesntHave('vehicleUsages')
                    ),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn (User $record) => auth()->user()->is_supervisor || auth()->user()->id === $record->id),
                Tables\Actions\EditAction::make()
                    ->visible(fn (User $record) => auth()->user()->is_supervisor || auth()->user()->id === $record->id),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => auth()->user()->is_supervisor && auth()->user()->id !== $record->id),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (User $record) => $record->is_active ? 'Desativar' : 'Ativar')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->visible(fn () => auth()->user()->is_supervisor)
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => $record->is_active ? 'Desativar Usuário' : 'Ativar Usuário')
                    ->modalDescription(fn (User $record) => $record->is_active 
                        ? 'Tem certeza que deseja desativar este usuário? Ele não poderá mais acessar o sistema.'
                        : 'Tem certeza que deseja ativar este usuário? Ele poderá acessar o sistema novamente.'
                    )
                    ->action(fn (User $record) => $record->update(['is_active' => !$record->is_active]))
                    ->successNotificationTitle(fn (User $record) => 
                        $record->is_active ? 'Usuário ativado com sucesso!' : 'Usuário desativado com sucesso!'
                    ),
                Tables\Actions\Action::make('reset_password')
                    ->label('Redefinir Senha')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->visible(fn (User $record) => auth()->user()->is_supervisor && auth()->user()->id !== $record->id)
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Nova Senha')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText('A senha deve ter pelo menos 8 caracteres'),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Nova Senha')
                            ->password()
                            ->required()
                            ->same('password')
                            ->helperText('Digite a mesma senha para confirmar'),
                    ])
                    ->action(function (array $data, User $record): void {
                        $record->update([
                            'password' => Hash::make($data['password'])
                        ]);
                    })
                    ->successNotificationTitle('Senha redefinida com sucesso!')
                    ->modalHeading('Redefinir Senha do Usuário')
                    ->modalDescription(fn (User $record) => "Você está redefinindo a senha do usuário: {$record->name}. Esta ação não pode ser desfeita.")
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Ativar Selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn () => auth()->user()->is_supervisor)
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->successNotificationTitle('Usuários ativados com sucesso!'),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desativar Selecionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn () => auth()->user()->is_supervisor)
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->successNotificationTitle('Usuários desativados com sucesso!'),
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Exportar Selecionados')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function ($records) {
                            return response()->streamDownload(function () use ($records) {
                                echo "Nome,E-mail,Telefone,Departamento,Cargo,Supervisor,Status,Criado em\n";
                                foreach ($records as $record) {
                                    echo implode(',', [
                                        '"' . str_replace('"', '""', $record->name) . '"',
                                        $record->email,
                                        $record->phone ?? '',
                                        '"' . str_replace('"', '""', $record->department ?? '') . '"',
                                        '"' . str_replace('"', '""', $record->position ?? '') . '"',
                                        $record->is_supervisor ? 'Sim' : 'Não',
                                        $record->is_active ? 'Ativo' : 'Inativo',
                                        $record->created_at->format('d/m/Y H:i'),
                                    ]) . "\n";
                                }
                            }, 'usuarios_' . now()->format('Y-m-d_H-i-s') . '.csv');
                        }),
                ]),
            ])
            ->headerActions([
                // Removido o botão de estatísticas
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->poll('60s'); // Atualiza a cada 60 segundos
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Se o usuário não for supervisor, só mostra o próprio registro
        if (!auth()->user()->is_supervisor) {
            $query->where('id', auth()->user()->id);
        }
        
        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
