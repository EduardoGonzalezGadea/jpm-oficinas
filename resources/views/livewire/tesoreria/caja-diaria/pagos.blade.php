<div>
	@if (session()->has('message'))
		<div class="alert alert-success alert-dismissible fade show" role="alert">
			{{ session('message') }}
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	@endif

	<div class="card">
		<div class="card-header">
			<div class="row align-items-center">
				<div class="col-md-6">
					<input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Buscar pagos...">
				</div>
				<div class="col-md-6 text-right">
					@can('crear_pagos')
						<button class="btn btn-primary" wire:click="crear">
							<i class="fas fa-plus"></i> Nuevo Pago
						</button>
					@endcan
				</div>
			</div>
		</div>
    <div class="card-body">
			<div class="table-responsive">
				<table class="table table-hover">
					<thead>
						<tr>
							<th>Concepto</th>
							<th>Monto</th>
							<th>Medio de Pago</th>
							<th>N° Comprobante</th>
							<th>Descripción</th>
							<th>Acciones</th>
						</tr>
					</thead>
					<tbody>
						@forelse($pagos as $pago)
							<tr>
								<td>{{ optional($pago->concepto)->nombre }}</td>
								<td>$ {{ number_format($pago->monto, 2) }}</td>
								<td>{{ $pago->medio_pago }}</td>
								<td>{{ $pago->numero_comprobante }}</td>
								<td>{{ $pago->descripcion }}</td>
								<td>
									<div class="btn-group">
										@can('editar_pagos')
											<button class="btn btn-sm btn-info" wire:click="editar({{ $pago->id }})">
												<i class="fas fa-edit"></i>
											</button>
										@endcan
										@can('eliminar_pagos')
											<button class="btn btn-sm btn-danger" wire:click="eliminar({{ $pago->id }})" onclick="return confirm('¿Está seguro de eliminar este pago?')">
												<i class="fas fa-trash"></i>
											</button>
										@endcan
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="6" class="text-center">No hay pagos registrados para esta fecha</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
			<div class="mt-3">
				{{ $pagos->links() }}
			</div>
		</div>


<!-- Modal para Crear/Editar Pago -->
	<div class="modal fade" wire:ignore.self id="modalPago" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="modalPagoLabel">{{ $pagoId ? 'Editar Pago' : 'Nuevo Pago' }}</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form wire:submit.prevent="guardar">
						<div class="form-group">
							<label for="concepto_id">Concepto *</label>
							<select class="form-control @error('concepto_id') is-invalid @enderror" wire:model="concepto_id">
								<option value="">Seleccione un concepto</option>
								@foreach($conceptos as $concepto)
									<option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>
								@endforeach
							</select>
							@error('concepto_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
						</div>
						<div class="form-group">
							<label for="monto">Monto *</label>
							<input type="number" class="form-control @error('monto') is-invalid @enderror" wire:model="monto" step="0.01">
							@error('monto') <span class="invalid-feedback">{{ $message }}</span> @enderror
						</div>
						<div class="form-group">
							<label for="medio_pago">Medio de Pago *</label>
							<input type="text" class="form-control @error('medio_pago') is-invalid @enderror" wire:model="medio_pago">
							@error('medio_pago') <span class="invalid-feedback">{{ $message }}</span> @enderror
						</div>
						<div class="form-group">
							<label for="numero_comprobante">N° Comprobante</label>
							<input type="text" class="form-control @error('numero_comprobante') is-invalid @enderror" wire:model="numero_comprobante">
							@error('numero_comprobante') <span class="invalid-feedback">{{ $message }}</span> @enderror
						</div>
						<div class="form-group">
							<label for="descripcion">Descripción</label>
							<textarea class="form-control @error('descripcion') is-invalid @enderror" wire:model="descripcion" rows="3"></textarea>
							@error('descripcion') <span class="invalid-feedback">{{ $message }}</span> @enderror
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
					<button type="button" class="btn btn-primary" wire:click="guardar">Guardar</button>
				</div>
			</div>
		</div>
	</div>

	@push('scripts')
	<script>
		document.addEventListener('livewire:load', function () {
			Livewire.hook('message.processed', (message, component) => {
				if (@this.showModal) {
					$('#modalPago').modal('show');
				} else {
					$('#modalPago').modal('hide');
				}
			});
		});
	</script>
	@endpush
